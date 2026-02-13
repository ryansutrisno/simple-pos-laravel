# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project Architecture Rules

### Single-Tenant Architecture
- Application uses single-tenant pattern - always access store via `Store::first()`
- No multi-tenancy support is implemented or planned

### Receipt Template System Architecture
- Templates are stored in JSON format with three required sections: header, body, footer
- `ReceiptTemplateService` is the single source of truth for template operations
- `ReceiptRenderer` handles ESC/POS code generation for thermal printers
- Templates can be global (store_id = null) or store-specific
- Only one default template per store is allowed (enforced by service layer)

### Transaction Flow
1. POS Livewire component manages cart state
2. Checkout creates Transaction, TransactionItems, and FinancialRecord
3. Stock is decremented via direct model calls (not through observer)
4. `transaction-completed` event is dispatched for printing
5. FinancialRecord tracks profit per transaction

### Bluetooth Printing Architecture
- Uses Web Bluetooth API with ESC/POS encoding
- Printer connection state stored in localStorage
- Receipt data fetched from API endpoint `/api/transactions/{id}`
- Client-side rendering using `@point-of-sale/receipt-printer-encoder`

### Filament Resource Architecture
- Custom pages for each resource (List, Create, Edit, View)
- Validation logic embedded in EditAction via `mutateFormDataUsing`
- Delete actions include business logic guards (e.g., prevent default template deletion)
