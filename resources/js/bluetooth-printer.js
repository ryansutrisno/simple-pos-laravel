import WebBluetoothReceiptPrinter from '@point-of-sale/webbluetooth-receipt-printer';
import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder';

class BluetoothPrinter {
    constructor() {
        this.printer = new WebBluetoothReceiptPrinter();
        this.connected = false;
        this.deviceId = localStorage.getItem('bluetooth_printer_id');
    }

    async connect() {
        try {
            if (!navigator.bluetooth) {
                alert('Browser tidak mendukung Bluetooth. Gunakan Chrome atau Edge.');
                return false;
            }

            const device = await navigator.bluetooth.requestDevice({
                filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }],
                optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
            });

            await this.printer.connect(device);
            this.connected = true;
            this.deviceId = device.id;
            this.storeDeviceId(device.id);
            this.updateStatusUI();

            return true;
        } catch (error) {
            console.error('Bluetooth connection error:', error);
            this.handleError(error);
            return false;
        }
    }

    async autoConnect() {
        if (!this.deviceId || !navigator.bluetooth) {
            return;
        }

        try {
            const devices = await navigator.bluetooth.getDevices();
            const device = devices.find(d => d.id === this.deviceId);

            if (device) {
                await this.printer.connect(device);
                this.connected = true;
                this.updateStatusUI();
            }
        } catch (error) {
            console.error('Auto-connect error:', error);
            this.connected = false;
            this.updateStatusUI();
        }
    }

    async disconnect() {
        try {
            await this.printer.disconnect();
            this.connected = false;
            this.deviceId = null;
            localStorage.removeItem('bluetooth_printer_id');
            this.updateStatusUI();
        } catch (error) {
            console.error('Disconnect error:', error);
        }
    }

    async printReceipt(transactionId) {
        if (!this.connected) {
            alert('Printer tidak terhubung. Silakan hubungkan printer terlebih dahulu.');
            return;
        }

        try {
            const response = await fetch(`/api/transactions/${transactionId}`);
            const data = await response.json();

            if (data && data.transaction) {
                this.printReceiptData(data.transaction);
            } else {
                alert('Data transaksi tidak ditemukan.');
            }
        } catch (error) {
            console.error('Error fetching transaction data:', error);
            alert('Gagal mengambil data transaksi.');
        }
    }

    async printReceiptData(transactionData) {
        if (!this.connected) {
            alert('Printer tidak terhubung.');
            return;
        }

        try {
            const encoder = new ReceiptPrinterEncoder({
                language: 'esc-pos',
                codepageMapping: 'epson'
            });

            let data = encoder
                .initialize()
                .align('center')
                .bold(true)
                .size('double-width', 'double-height')
                .text(transactionData.store.name)
                .bold(false)
                .size('normal')
                .text(transactionData.store.address)
                .text('Tel: ' + transactionData.store.phone)
                .newline()
                .text('================================')
                .newline()
                .align('left')
                .text('ID: ' + transactionData.id)
                .text('Tgl: ' + transactionData.date)
                .text('Kasir: ' + transactionData.cashier)
                .newline()
                .text('--------------------------------')
                .newline();

            transactionData.items.forEach(item => {
                const name = item.name;
                const qty = 'x' + item.quantity;
                const price = this.formatCurrency(item.price);

                data = data.text(`${name.padEnd(20, ' ')}${qty.padEnd(6, ' ')}${price}`);
                data = data.text(`   ${this.formatCurrency(item.subtotal).padStart(28, ' ')}`);
                data = data.newline();
            });

            const paymentMethodMap = {
                'cash': 'Tunai',
                'transfer': 'Transfer',
                'qris': 'QRIS'
            };

            data = data
                .text('--------------------------------')
                .newline()
                .text('Subtotal:'.padEnd(20, ' ') + this.formatCurrency(transactionData.payment.total).padStart(10, ' '))
                .text('Pembayaran: ' + paymentMethodMap[transactionData.payment.method] || transactionData.payment.method)
                .text('Dibayar:'.padEnd(20, ' ') + this.formatCurrency(transactionData.payment.cash_amount || transactionData.payment.total).padStart(10, ' '))
                .text('Kembali:'.padEnd(20, ' ') + this.formatCurrency(transactionData.payment.change_amount || 0).padStart(10, ' '))
                .newline()
                .text('================================')
                .newline()
                .bold(true)
                .align('center')
                .size('double-width')
                .text('Total:'.padEnd(12, ' ') + this.formatCurrency(transactionData.payment.total))
                .bold(false)
                .size('normal')
                .newline()
                .text('================================')
                .newline()
                .text('Terima kasih atas')
                .text('kunjungan Anda!')
                .newline()
                .barcode(transactionData.id, 'CODE128', {
                    width: 2,
                    height: 60
                })
                .newline()
                .newline()
                .newline()
                .cut('partial')
                .encode();

            await this.printer.print(data);
        } catch (error) {
            console.error('Print receipt data error:', error);
            alert('Gagal mencetak struk. Pastikan printer terhubung dan kertas tersedia.');
        }
    }

    async printReturnReceipt(returnId) {
        if (!this.connected) {
            alert('Printer tidak terhubung. Silakan hubungkan printer terlebih dahulu.');
            return;
        }

        try {
            const response = await fetch(`/api/returns/${returnId}/receipt`);
            const result = await response.json();

            if (result && result.success && result.data) {
                this.printReturnReceiptData(result.data.return);
            } else {
                alert('Data return tidak ditemukan.');
            }
        } catch (error) {
            console.error('Error fetching return data:', error);
            alert('Gagal mengambil data return.');
        }
    }

    async printReturnReceiptData(returnData) {
        if (!this.connected) {
            alert('Printer tidak terhubung.');
            return;
        }

        try {
            const encoder = new ReceiptPrinterEncoder({
                language: 'esc-pos',
                codepageMapping: 'epson'
            });

            let data = encoder
                .initialize()
                .align('center')
                .bold(true)
                .size('double-width', 'double-height')
                .text(returnData.store.name)
                .bold(false)
                .size('normal')
                .text(returnData.store.address)
                .text('Tel: ' + returnData.store.phone)
                .newline()
                .bold(true)
                .size('double-width')
                .text('RETURN RECEIPT')
                .bold(false)
                .size('normal')
                .newline()
                .text('================================')
                .newline()
                .align('left')
                .text('Return #: ' + returnData.return_number)
                .text('ID: ' + returnData.id)
                .text('Ref Trans: #' + returnData.transaction_id)
                .text('Tgl: ' + returnData.date)
                .text('Kasir: ' + returnData.cashier)
                .newline()
                .text('--------------------------------')
                .newline();

            returnData.items.forEach(item => {
                const name = item.name;
                const qty = 'x' + item.quantity;
                const price = this.formatCurrency(item.price);

                data = data.text(`${name.padEnd(20, ' ')}${qty.padEnd(6, ' ')}${price}`);
                data = data.text(`   ${this.formatCurrency(item.subtotal).padStart(28, ' ')}`);
                data = data.newline();

                if (item.is_exchange && item.exchange_product_name) {
                    data = data.text(`  -> Tukar: ${item.exchange_product_name} x${item.exchange_quantity}`);
                    data = data.text(`     ${this.formatCurrency(item.exchange_subtotal).padStart(26, ' ')}`);
                    data = data.newline();
                }
            });

            const refundMethodMap = {
                'cash': 'Tunai',
                'store_credit': 'Store Credit',
                'original_method': 'Metode Asal'
            };

            data = data
                .text('--------------------------------')
                .newline()
                .text('Alasan: ' + (returnData.reason_category || '-'))
                .newline()
                .text('--------------------------------')
                .newline()
                .text('Total Refund:'.padEnd(20, ' ') + this.formatCurrency(returnData.refund.total_refund).padStart(10, ' '))
                .newline();

            if (returnData.refund.total_exchange_value > 0) {
                data = data.text('Nilai Tukar:'.padEnd(20, ' ') + this.formatCurrency(returnData.refund.total_exchange_value).padStart(10, ' '));
                data = data.newline();
            }

            if (returnData.refund.selisih_amount != 0) {
                const selisihLabel = returnData.refund.selisih_amount > 0 ? 'Perlu Dibayar:' : 'Direfund:';
                data = data
                    .bold(true)
                    .text(selisihLabel.padEnd(20, ' ') + this.formatCurrency(Math.abs(returnData.refund.selisih_amount)).padStart(10, ' '))
                    .bold(false)
                    .newline();
            }

            data = data
                .text('Metode: ' + (refundMethodMap[returnData.refund.method] || returnData.refund.method_label || '-'))
                .newline()
                .text('================================')
                .newline()
                .bold(true)
                .align('center')
                .text('Terima kasih atas pengertian Anda')
                .bold(false)
                .newline()
                .text('================================')
                .newline()
                .newline()
                .newline()
                .cut('partial')
                .encode();

            await this.printer.print(data);
        } catch (error) {
            console.error('Print return receipt data error:', error);
            alert('Gagal mencetak struk return. Pastikan printer terhubung dan kertas tersedia.');
        }
    }

    formatCurrency(amount) {
        return 'Rp ' + (amount || 0).toLocaleString('id-ID');
    }

    updateStatusUI() {
        const statusIcon = document.getElementById('printer-icon-status');
        const statusText = document.getElementById('printer-status-text');
        const connectButton = document.querySelector('button[onclick="window.connectPrinter()"]');

        if (statusIcon && statusText) {
            if (this.connected) {
                statusIcon.innerHTML = '<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />';
                statusIcon.classList.remove('text-red-500');
                statusIcon.classList.add('text-green-500');
                statusText.textContent = 'Printer terhubung';
            } else {
                statusIcon.innerHTML = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />';
                statusIcon.classList.remove('text-green-500');
                statusIcon.classList.add('text-red-500');
                statusText.textContent = 'Printer tidak terhubung';
            }
        }

        if (connectButton) {
            if (this.connected) {
                connectButton.textContent = 'Putuskan Printer';
                connectButton.setAttribute('onclick', 'window.disconnectPrinter()');
                connectButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                connectButton.classList.add('bg-red-600', 'hover:bg-red-700');
            } else {
                connectButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M7.5 6v4.5a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V6a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75ZM7.5 15v4.5a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V15a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75Z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M4.5 3a2.25 2.25 0 0 0-2.25 2.25v11.25c0 .896.432 1.69 1.098 2.188l1.9-4.188c.237-.521.754-.874 1.328-.874h7.5a1.5 1.5 0 0 0 1.328-.874l1.9-4.188A2.252 2.252 0 0 0 19.5 5.25V5.25a2.25 2.25 0 0 0-2.25-2.25H4.5ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" clip-rule="evenodd" />
                    </svg>
                    Hubungkan Printer
                `;
                connectButton.setAttribute('onclick', 'window.connectPrinter()');
                connectButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                connectButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }
        }
    }

    storeDeviceId(id) {
        localStorage.setItem('bluetooth_printer_id', id);
    }

    handleError(error) {
        console.error('Bluetooth printer error:', error);

        let message = 'Terjadi kesalahan pada printer.';

        if (error.name === 'NotFoundError') {
            message = 'Printer tidak ditemukan. Pastikan printer dalam mode pairing.';
        } else if (error.name === 'SecurityError') {
            message = 'Akses Bluetooth ditolak. Harap izinkan akses di pengaturan browser.';
        } else if (error.name === 'NetworkError') {
            message = 'Gagal terhubung ke printer. Coba lagi.';
        }

        alert(message);
    }
}

window.bluetoothPrinter = new BluetoothPrinter();
window.connectPrinter = () => window.bluetoothPrinter.connect();
window.disconnectPrinter = () => window.bluetoothPrinter.disconnect();
window.printReceipt = (transactionId) => window.bluetoothPrinter.printReceipt(transactionId);
window.printTransactionReceipt = (transactionId) => window.bluetoothPrinter.printReceipt(transactionId);
window.printReturnReceipt = (returnId) => window.bluetoothPrinter.printReturnReceipt(returnId);
