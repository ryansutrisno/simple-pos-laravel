<?php

namespace App\Services;

use App\Models\ReceiptTemplate;

class ReceiptRenderer
{
    protected ReceiptTemplate $template;
    protected array $data;

    public function __construct(ReceiptTemplate $template, array $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Render the complete receipt.
     */
    public function render(): string
    {
        $output = '';
        
        // Render header
        $output .= $this->renderHeader();
        
        // Render body
        $output .= $this->renderBody();
        
        // Render footer
        $output .= $this->renderFooter();

        return $output;
    }

    /**
     * Render the header section.
     */
    protected function renderHeader(): string
    {
        $header = $this->template->template_data['header'] ?? [];
        $output = '';

        // Logo (placeholder - would need actual image processing)
        if ($header['show_logo'] ?? false) {
            $output .= $this->formatText('[LOGO]', $this->getAlignment());
        }

        // Store name
        if ($header['show_store_name'] ?? false) {
            $storeName = $this->data['store']['name'] ?? 'Store Name';
            $output .= $this->formatText($storeName, $this->getAlignment(), true);
        }

        // Store address
        if ($header['show_store_address'] ?? false) {
            $storeAddress = $this->data['store']['address'] ?? '';
            if (!empty($storeAddress)) {
                $output .= $this->formatText($storeAddress, $this->getAlignment());
            }
        }

        // Store phone
        if ($header['show_store_phone'] ?? false) {
            $storePhone = $this->data['store']['phone'] ?? '';
            if (!empty($storePhone)) {
                $output .= $this->formatText('Tel: ' . $storePhone, $this->getAlignment());
            }
        }

        // Tagline
        if ($header['show_tagline'] ?? false) {
            $tagline = $this->data['store']['tagline'] ?? '';
            if (!empty($tagline)) {
                $output .= $this->formatText($tagline, $this->getAlignment());
            }
        }

        // Custom header message
        $customMessage = $header['custom_header_message'] ?? '';
        if (!empty($customMessage)) {
            $output .= $this->formatText($customMessage, $this->getAlignment());
        }

        // Separator
        $output .= $this->getSeparator();

        return $output;
    }

    /**
     * Render the body section.
     */
    protected function renderBody(): string
    {
        $body = $this->template->template_data['body'] ?? [];
        $output = '';

        // Transaction ID
        if ($body['show_transaction_id'] ?? false) {
            $transactionId = $this->data['transaction']['id'] ?? '';
            $output .= $this->formatText('ID: ' . $transactionId, 'left');
        }

        // Date
        if ($body['show_date'] ?? false) {
            $date = $this->data['transaction']['date'] ?? '';
            $output .= $this->formatText('Tgl: ' . $date, 'left');
        }

        // Cashier name
        if ($body['show_cashier_name'] ?? false) {
            $cashier = $this->data['transaction']['cashier'] ?? '';
            $output .= $this->formatText('Kasir: ' . $cashier, 'left');
        }

        $output .= "\n";

        // Items header
        if ($body['show_items_header'] ?? false) {
            $output .= $this->getSeparator();
            $output .= "\n";
        }

        // Items
        $items = $this->data['items'] ?? [];
        foreach ($items as $item) {
            $output .= $this->formatItem($item, $body['item_format'] ?? 'name_price_quantity');
        }

        // Subtotal
        if ($body['show_subtotal'] ?? false) {
            $output .= $this->getSeparator();
            $total = $this->data['payment']['total'] ?? 0;
            $output .= $this->formatText('Subtotal: ' . $this->formatCurrency($total), 'left');
        }

        return $output;
    }

    /**
     * Render the footer section.
     */
    protected function renderFooter(): string
    {
        $footer = $this->template->template_data['footer'] ?? [];
        $output = '';

        $output .= $this->getSeparator();

        // Payment method
        if ($footer['show_payment_method'] ?? false) {
            $method = $this->data['payment']['method'] ?? 'cash';
            $methodName = $this->getPaymentMethodName($method);
            $output .= $this->formatText('Pembayaran: ' . $methodName, 'left');
        }

        // Cash received
        if ($footer['show_cash_received'] ?? false) {
            $cashReceived = $this->data['payment']['cash_received'] ?? 0;
            $output .= $this->formatText('Dibayar: ' . $this->formatCurrency($cashReceived), 'left');
        }

        // Change amount
        if ($footer['show_change'] ?? false) {
            $change = $this->data['payment']['change_amount'] ?? 0;
            $output .= $this->formatText('Kembali: ' . $this->formatCurrency($change), 'left');
        }

        $output .= "\n";
        $output .= $this->getSeparator();

        // Total (bold and large)
        $total = $this->data['payment']['total'] ?? 0;
        $output .= $this->formatText('Total: ' . $this->formatCurrency($total), $this->getAlignment(), true);

        $output .= $this->getSeparator();

        // Custom footer message
        $customMessage = $footer['custom_footer_message'] ?? '';
        if (!empty($customMessage)) {
            $output .= $this->formatText($customMessage, $this->getAlignment());
            $output .= "\n";
        }

        // Barcode
        if ($footer['show_barcode'] ?? false) {
            $transactionId = $this->data['transaction']['id'] ?? '';
            $output .= $this->formatText("[BARCODE:{$transactionId}]", 'center');
        }

        // QR Code
        if ($footer['show_qr_code'] ?? false) {
            $output .= $this->formatText("[QR CODE]", 'center');
        }

        // Add some space and cut instruction
        $output .= "\n\n\n";
        $output .= $this->formatText('[CUT]', 'center');

        return $output;
    }

    /**
     * Format an individual item.
     */
    protected function formatItem(array $item, string $format): string
    {
        $name = $item['name'] ?? '';
        $quantity = $item['quantity'] ?? 1;
        $price = $item['price'] ?? 0;
        $subtotal = $item['subtotal'] ?? 0;

        switch ($format) {
            case 'name_price_quantity':
                return sprintf(
                    "%s\n   x%d %s\n   %s\n",
                    $name,
                    $quantity,
                    $this->formatCurrency($price),
                    $this->formatCurrency($subtotal)
                );

            case 'name_only':
                return sprintf("%s x%d\n", $name, $quantity);

            case 'price_only':
                return sprintf("%s\n", $this->formatCurrency($subtotal));

            default:
                return sprintf("%s x%d - %s\n", $name, $quantity, $this->formatCurrency($subtotal));
        }
    }

    /**
     * Format text with alignment and styling.
     */
    protected function formatText(string $text, string $alignment = 'left', bool $bold = false): string
    {
        $styledText = $bold ? "[BOLD]{$text}[/BOLD]" : $text;
        
        // In a real implementation, this would add actual formatting codes
        // For now, we'll just return the text with alignment hints
        return match($alignment) {
            'center' => str_pad($text, 40, ' ', STR_PAD_BOTH) . "\n",
            'right' => str_pad($text, 40, ' ', STR_PAD_LEFT) . "\n",
            default => $text . "\n",
        };
    }

    /**
     * Get the separator based on styling.
     */
    protected function getSeparator(): string
    {
        $styling = $this->template->template_data['styling'] ?? [];
        $style = $styling['separator_style'] ?? 'dashes';

        return match($style) {
            'dots' => str_repeat('.', 40) . "\n",
            'line' => str_repeat('-', 40) . "\n",
            default => str_repeat('=', 40) . "\n",
        };
    }

    /**
     * Get text alignment.
     */
    protected function getAlignment(): string
    {
        return $this->template->template_data['styling']['text_alignment'] ?? 'left';
    }

    /**
     * Format currency.
     */
    protected function formatCurrency(float|int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get payment method display name.
     */
    protected function getPaymentMethodName(string $method): string
    {
        return match($method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            default => ucfirst($method),
        };
    }
}
