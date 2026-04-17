<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        Carbon::setLocale('pt_BR');
        $user = auth()->user();
        $subscriptions = $user->subscriptions()->where('status', 'active')->get();
        
        // Forecasting logic for the next 6 months
        $forecast = [];
        $monthsToForecast = 6;
        $salary = $user->salary ?? 0;

        for ($i = 0; $i < $monthsToForecast; $i++) {
            $date = Carbon::now()->addMonths($i);
            $monthName = $date->translatedFormat('F');
            $year = $date->year;
            
            $monthTotal = 0;
            $activeInMonth = [];

            foreach ($subscriptions as $sub) {
                $startDate = Carbon::parse($sub->start_date);
                
                // Check if subscription is active in this specific future month
                $isActive = false;
                if ($sub->is_indefinite) {
                    $isActive = true;
                } else {
                    $endDate = $startDate->copy()->addMonths($sub->duration_months);
                    if ($date->between($startDate->startOfMonth(), $endDate->endOfMonth())) {
                        $isActive = true;
                    }
                }

                if ($isActive) {
                    $monthTotal += $sub->amount;
                    $activeInMonth[] = $sub;
                }
            }

            $forecast[] = [
                'month' => ucfirst($monthName),
                'year' => $year,
                'total_expenses' => $monthTotal,
                'balance' => $salary - $monthTotal,
                'subscriptions' => $activeInMonth
            ];
        }

        return view('subscriptions.index', compact('subscriptions', 'forecast', 'salary'));
    }

    public function create()
    {
        return view('subscriptions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_day' => 'required|integer|min:1|max:31',
            'category' => 'required|string',
            'type' => 'required|in:subscription,installment',
            'duration_months' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
        ]);

        $user = auth()->user();

        Subscription::create([
            'user_id' => $user->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'due_day' => $validated['due_day'],
            'category' => $validated['category'],
            'is_indefinite' => $validated['type'] === 'subscription',
            'duration_months' => $validated['type'] === 'installment' ? $validated['duration_months'] : null,
            'start_date' => $validated['start_date'],
            'status' => 'active',
        ]);

        return redirect()->route('subscriptions.index')->with('success', 'Mensalidade cadastrada com sucesso!');
    }

    public function destroy(Subscription $subscription)
    {
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Mensalidade removida com sucesso!');
    }

    public function edit(Subscription $subscription)
    {
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }
        return view('subscriptions.edit', compact('subscription'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_day' => 'required|integer|min:1|max:31',
            'category' => 'required|string',
            'type' => 'required|in:subscription,installment',
            'duration_months' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
        ]);

        $subscription->update([
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'due_day' => $validated['due_day'],
            'category' => $validated['category'],
            'is_indefinite' => $validated['type'] === 'subscription',
            'duration_months' => $validated['type'] === 'installment' ? $validated['duration_months'] : null,
            'start_date' => $validated['start_date'],
        ]);

        return redirect()->route('subscriptions.index')->with('success', 'Mensalidade atualizada com sucesso!');
    }
}
