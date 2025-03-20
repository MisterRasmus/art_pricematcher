# ART PriceMatcher Module Changes

## Controller Name Update

All references to `AdminPriceMatcherController` have been updated to `AdminPriceMatcher` to align with PrestaShop 8 standards. This includes:

### PHP Files Updated
- `AdminPriceMatcherController.php` - Updated template paths and simplified rendering
- `Dashboard.php` - Updated all admin links
- `Statistics.php` - Updated pagination links and AJAX URLs
- `Settings.php` - Updated form action links
- `Competitors.php` - Updated form action links
- `Active_Discounts.php` - Updated pagination links
- `art_pricematcher.php` - Updated controller name checks in hooks

### Template Files Updated
- `competitors.tpl` - Updated admin links to use the correct format
- `error.tpl` - Updated dashboard link
- Other template files now use the correct path format

## Template Path Format

Template paths have been standardized to use the PrestaShop 8 format:
- Changed from: `module:art_pricematcher/views/templates/admin/layout.tpl`
- To: `../modules/art_pricematcher/views/templates/admin/layout.tpl`

## Admin Link Format

Admin links have been updated to use the simplified format:
- Changed from: `$this->context->link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => 'dashboard'])`
- To: `$this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard'`

## Verification Tools

- `verify.php` - Checks for key files and their existence
- `verify_controller.php` - Specifically checks for any remaining instances of the old controller name

## Next Steps

1. Clear the PrestaShop cache after uploading these changes
2. Access the module via: `https://your-shop.com/admin-xxx/index.php?controller=AdminPriceMatcher&token=YOUR_TOKEN`
3. Run the verification scripts to ensure all issues have been resolved
