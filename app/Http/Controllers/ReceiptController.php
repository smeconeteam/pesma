<?php

namespace App\Http\Controllers;

use App\Models\BillPayment;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Display receipt for a payment
     */
    public function show(BillPayment $payment)
    {
        // Eager load all necessary relationships
        $payment->load([
            'bill.user.residentProfile',
            'bill.billingType',
            'bill.room.block.dorm',
            'bill.registration',
            'paidByUser.residentProfile',
            'paymentMethod',
            'bankAccount.paymentMethod',
            'verifiedBy'
        ]);

        // Get institution data for logo
        $institution = \App\Models\Institution::first();

        // Get room members if this is a room-based payment
        $roomMembers = [];
        if ($payment->bill->room_id) {
            $roomMembers = \App\Models\RoomResident::where('room_id', $payment->bill->room_id)
                ->whereNull('check_out_date')
                ->with('user')
                ->get();
        }

        // Parse PIC payment details if applicable
        $picPaymentDetails = [];
        if ($payment->is_pic_payment && $payment->notes) {
            // Extract bill allocations from notes
            // Format: "Rp 500,000 untuk BILL-XXX"
            preg_match_all('/Rp ([\\d,.]+) untuk (BILL-[\\w-]+)/', $payment->notes, $matches);
            
            if (!empty($matches[1]) && !empty($matches[2])) {
                foreach ($matches[2] as $index => $billNumber) {
                    $picPaymentDetails[] = [
                        'bill_number' => $billNumber,
                        'amount' => $matches[1][$index]
                    ];
                }
            }
        }

        return view('receipt', compact('payment', 'institution', 'roomMembers', 'picPaymentDetails'));
    }

    /**
     * Download receipt as PNG (handled by frontend HTML2Canvas)
     * This method is currently a placeholder for future server-side implementation
     */
    public function download(BillPayment $payment)
    {
        // For now, redirect to show view
        // PNG generation is handled client-side via HTML2Canvas
        return redirect()->route('receipt.show', $payment);
    }
}
