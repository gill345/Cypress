# Notification System Enhancement - Changelog

## New Updates
- Multi-user notification subscription system
- Subscription from the map interface
- Notification status filtering
- Improved email content for notifications

## DETAILED UPDATES

#### setup_subscriptions.php ( new file )
- Creates new `report_subscriptions` table for multi-user notifications
- **Action Required**: Run this file first to create the new table structure
```php
// Run in order:
php setup_subscriptions.php
```
#### migrate_existing_subscriptions.php ( new file )
- Transfers data from `notify_updates` column to new table
- **Action Required**: Run after setup_subscriptions.php
```php
// Run after setup_subscriptions.php:
php migrate_existing_subscriptions.php
```
#### update_subscription.php ( new file )
- New file for handling subscription update through popups 
- Manages subscribe/unsubscribe actions
- Sends confirmation emails

#### index.php
- Added notification status filter 
- Added notification subscription toggle in report popups
- Added email prompt for new subscriptions
- Added unsubscribe confirmation
- Added subscription status indicators ( "Subscribe" means has not been subscribed and vice versa)

#### admin.php
- Updated notification system to support multiple subscribers
- Modified email content to be more objective

#### report.php
- Added subscribed reports to report_subscriptions table
- Added email validation


## SET UP INSTRUCTIONS ( MERGE ORDER )

1. Run database migrations in order:
   ```bash
   php setup_subscriptions.php
   php migrate_existing_subscriptions.php
   ```
2. Verify the `report_subscriptions` table is created
3. Check existing subscriptions are migrated correctly ( report with notification subscriptions should go here )

### Testing Steps ( make sure to have 2 real emails to test )
1. Use admin account to create a new report with notification enabled for email A, set it to "progress" ( A should receive 2 mails at this step )
2. Switch to another account and subscribe to the above report thru popup using email B ( B should receive a confirmation mail at this step ) 
3. Use admin account and resolve the report ( Both A and B should receive an email for "resolved" )
4. Unsubscribe the notification from the admin account ( which is email A) and delete the report. 
5. Verify that B receives a notification for "delete" and A won't be receiving anything 
6. Test the notification status filter!

### Notes
- All email content has been updated to be more objective
- Filters reset to "All" on page refresh 