<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::all();
        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);
        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'required|string|in:EXPENSE,SPENDING,INCOME,EARNING',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $transaction = Transaction::create($validatedData);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'sometimes|string|in:EXPENSE,SPENDING,INCOME,EARNING',
            'amount' => 'sometimes|numeric',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
        ]);

        $transaction->update($validatedData);

        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->json(null, 204);
    }
}