# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project-Specific Coding Rules

### Receipt Template System
- Receipt templates use `ReceiptTemplateService` for all template operations (getActiveTemplate, createTemplate, updateTemplate, deleteTemplate)
- Template data structure must include: `header`, `body`, `footer` sections with specific boolean flags and options
- Use `ReceiptRenderer` class to render receipts from templates - it handles formatting, alignment, and ESC/POS codes
- Template validation is required before saving - use `ReceiptTemplateService::validateTemplateData()`
- Store model has `receipt_template_id` field for per-store template overrides

### Financial Records
- Financial records are auto-created on transaction checkout in `Pos::checkout()` method
- Always create FinancialRecord with type 'sales', profit calculation, and transaction_id link
- Profit is calculated as: `selling_price - purchase_price` per item

### Store Access Pattern
- Application uses single-tenant architecture - always use `Store::first()` to get the store instance
- Store model has `receiptTemplates()` and `activeReceiptTemplate()` relationships

### Livewire POS Events
- After checkout, dispatch `transaction-completed` event with: transactionId, templateId, transactionData
- POS component uses `WithPagination` trait for product listing
- Cart items include: product_id, name, purchase_price, selling_price, quantity, profit

### API Endpoints
- Transaction data for printing: `GET /api/transactions/{id}` returns formatted data with template info
- Receipt preview: `GET /api/transactions/{id}/preview?template_id={id}` for template previews
- Available templates: `GET /api/transactions/templates` returns all active templates

### Product Stock Management
- Stock is decremented in `Pos::checkout()` using `Product::find()->decrement('stock', quantity)`
- ProductObserver exists - check it before modifying product-related logic

### Filament Resource Patterns
- ReceiptTemplateResource has custom pages: ListReceiptTemplates, CreateReceiptTemplate, EditReceiptTemplate, ViewReceiptTemplate
- Use `mutateFormDataUsing` in EditAction for validation before saving
- Delete actions prevent deletion of default templates
