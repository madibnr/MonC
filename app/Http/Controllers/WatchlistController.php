<?php

namespace App\Http\Controllers;

use App\Models\PlateDetectionLog;
use App\Models\WatchlistPlate;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    /**
     * Display watchlist with filters.
     */
    public function index(Request $request)
    {
        $query = WatchlistPlate::with('createdByUser')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $normalized = PlateDetectionLog::normalizePlate($request->search);
            $query->where(function ($q) use ($request, $normalized) {
                $q->where('plate_number_normalized', 'like', "%{$normalized}%")
                    ->orWhere('vehicle_owner', 'like', "%{$request->search}%")
                    ->orWhere('reason', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('alert_level')) {
            $query->where('alert_level', $request->alert_level);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $watchlist = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => WatchlistPlate::count(),
            'active' => WatchlistPlate::active()->count(),
            'critical' => WatchlistPlate::active()->byLevel('critical')->count(),
            'high' => WatchlistPlate::active()->byLevel('high')->count(),
        ];

        return view('ai.watchlist.index', compact('watchlist', 'stats'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('ai.watchlist.create');
    }

    /**
     * Store new watchlist entry.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string|max:20',
            'alert_level' => 'required|in:low,medium,high,critical',
            'reason' => 'nullable|string|max:255',
            'vehicle_owner' => 'nullable|string|max:255',
            'vehicle_description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'notify_telegram' => 'boolean',
        ]);

        $normalized = PlateDetectionLog::normalizePlate($request->plate_number);

        // Check for duplicate
        if (WatchlistPlate::where('plate_number_normalized', $normalized)->exists()) {
            return back()->withErrors(['plate_number' => 'This plate number is already on the watchlist.'])->withInput();
        }

        WatchlistPlate::create([
            'plate_number' => strtoupper(trim($request->plate_number)),
            'plate_number_normalized' => $normalized,
            'alert_level' => $request->alert_level,
            'reason' => $request->reason,
            'vehicle_owner' => $request->vehicle_owner,
            'vehicle_description' => $request->vehicle_description,
            'notes' => $request->notes,
            'is_active' => true,
            'notify_telegram' => $request->boolean('notify_telegram'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('ai.watchlist.index')
            ->with('success', "Plate {$request->plate_number} added to watchlist.");
    }

    /**
     * Show edit form.
     */
    public function edit(WatchlistPlate $watchlist)
    {
        return view('ai.watchlist.edit', compact('watchlist'));
    }

    /**
     * Update watchlist entry.
     */
    public function update(Request $request, WatchlistPlate $watchlist)
    {
        $request->validate([
            'plate_number' => 'required|string|max:20',
            'alert_level' => 'required|in:low,medium,high,critical',
            'reason' => 'nullable|string|max:255',
            'vehicle_owner' => 'nullable|string|max:255',
            'vehicle_description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'notify_telegram' => 'boolean',
        ]);

        $normalized = PlateDetectionLog::normalizePlate($request->plate_number);

        // Check for duplicate (excluding current)
        if (WatchlistPlate::where('plate_number_normalized', $normalized)
            ->where('id', '!=', $watchlist->id)
            ->exists()) {
            return back()->withErrors(['plate_number' => 'This plate number is already on the watchlist.'])->withInput();
        }

        $watchlist->update([
            'plate_number' => strtoupper(trim($request->plate_number)),
            'plate_number_normalized' => $normalized,
            'alert_level' => $request->alert_level,
            'reason' => $request->reason,
            'vehicle_owner' => $request->vehicle_owner,
            'vehicle_description' => $request->vehicle_description,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
            'notify_telegram' => $request->boolean('notify_telegram'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('ai.watchlist.index')
            ->with('success', "Watchlist entry updated for {$request->plate_number}.");
    }

    /**
     * Delete watchlist entry.
     */
    public function destroy(WatchlistPlate $watchlist)
    {
        $plate = $watchlist->plate_number;
        $watchlist->delete();

        return redirect()->route('ai.watchlist.index')
            ->with('success', "Plate {$plate} removed from watchlist.");
    }

    /**
     * Toggle active status (AJAX).
     */
    public function toggleActive(WatchlistPlate $watchlist)
    {
        $watchlist->update([
            'is_active' => ! $watchlist->is_active,
            'updated_by' => auth()->id(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $watchlist->is_active,
                'message' => $watchlist->is_active
                    ? "Watchlist entry activated for {$watchlist->plate_number}"
                    : "Watchlist entry deactivated for {$watchlist->plate_number}",
            ]);
        }

        return back()->with('success', $watchlist->is_active
            ? "Watchlist entry activated for {$watchlist->plate_number}"
            : "Watchlist entry deactivated for {$watchlist->plate_number}");
    }
}
