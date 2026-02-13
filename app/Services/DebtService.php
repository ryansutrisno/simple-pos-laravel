<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Models\DebtPayment;
use App\Models\PurchaseOrder;
use App\Models\SupplierDebt;
use Illuminate\Support\Facades\Auth;

class DebtService
{
    public function createDebtFromPurchaseOrder(PurchaseOrder $purchaseOrder, ?\DateTime $dueDate = null): ?SupplierDebt
    {
        if ($purchaseOrder->payment_status->value === 'paid') {
            return null;
        }

        return SupplierDebt::create([
            'supplier_id' => $purchaseOrder->supplier_id,
            'purchase_order_id' => $purchaseOrder->id,
            'total_amount' => $purchaseOrder->total_amount,
            'paid_amount' => 0,
            'remaining_amount' => $purchaseOrder->total_amount,
            'debt_date' => $purchaseOrder->received_date ?? now(),
            'due_date' => $dueDate ?? now()->addDays(30),
            'status' => DebtStatus::Pending,
            'user_id' => Auth::id(),
        ]);
    }

    public function recordPayment(SupplierDebt $debt, float $amount, string $paymentMethod, ?string $note = null): DebtPayment
    {
        $payment = DebtPayment::create([
            'supplier_debt_id' => $debt->id,
            'amount' => $amount,
            'payment_date' => now(),
            'payment_method' => $paymentMethod,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);

        $this->updatePurchaseOrderPaymentStatus($debt);

        return $payment;
    }

    protected function updatePurchaseOrderPaymentStatus(SupplierDebt $debt): void
    {
        if ($debt->purchaseOrder) {
            $debt->purchaseOrder->update([
                'payment_status' => match ($debt->status) {
                    DebtStatus::Paid => 'paid',
                    DebtStatus::Partial => 'partial',
                    default => 'unpaid',
                },
            ]);
        }
    }

    public function getOverdueDebts()
    {
        return SupplierDebt::overdue()->with(['supplier', 'purchaseOrder'])->get();
    }

    public function getTotalOutstandingDebt(): float
    {
        return SupplierDebt::whereNotIn('status', [DebtStatus::Paid])->sum('remaining_amount');
    }

    public function getDebtSummaryBySupplier(): array
    {
        return SupplierDebt::whereNotIn('status', [DebtStatus::Paid])
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($debts) {
                return [
                    'supplier' => $debts->first()->supplier,
                    'total_debt' => $debts->sum('total_amount'),
                    'total_paid' => $debts->sum('paid_amount'),
                    'total_remaining' => $debts->sum('remaining_amount'),
                    'debts_count' => $debts->count(),
                ];
            })
            ->toArray();
    }
}
