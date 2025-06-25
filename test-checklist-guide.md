# ICV Management System - Test Execution Checklist

## How to Use This Checklist
1. Open `test-checklist.csv` in Excel, Google Sheets, or any spreadsheet application
2. Update the Status column as you progress: Not Started → In Progress → Completed
3. Mark Pass/Fail based on test results
4. Add notes for any issues or observations
5. Track who performed the test and when

## Test Progress Summary
- **Total Tests**: 60
- **Critical Priority**: 2
- **High Priority**: 35
- **Medium Priority**: 19
- **Low Priority**: 4

## Test Categories
- **Authentication & Authorization**: 5 tests
- **User Management**: 4 tests
- **ICV Management & Workflow**: 8 tests
- **Izin Management & Workflow**: 24 tests
- **Email Notifications**: 6 tests (ICV) + 5 tests (Izin)
- **Data Management**: 4 tests (ICV) + 2 tests (Izin)
- **Error Handling**: 3 tests
- **Performance**: 2 tests
- **Security**: 2 tests
- **Migration**: 2 tests

## Test Data Setup Required
Before starting tests, ensure you have:
- [ ] Admin user account (username: admin, password: password123, status: aktif)
- [ ] Regular user account (username: user1, password: password123, status: aktif)
- [ ] Inactive user account (username: inactive, password: password123, status: tidak aktif)
- [ ] Sample ICV application data
- [ ] Email testing configuration (MailHog or similar)
- [ ] Clean database state

## Environment Checklist
- [ ] Laravel application running
- [ ] Database connected and migrated
- [ ] Email configuration working
- [ ] Filament admin panel accessible
- [ ] File upload directories writable
- [ ] Queue worker running (for email notifications)

## Quick Test Commands
```bash
# Run database migrations
php artisan migrate:fresh --seed

# Start queue worker for email testing
php artisan queue:work

# Run Laravel server
php artisan serve

# Clear cache if needed
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Test Result Definitions
- **Pass**: Test completed successfully, all expectations met
- **Fail**: Test failed, issue found that needs fixing
- **Blocked**: Cannot complete test due to dependency or environment issue
- **Skip**: Test not applicable or deferred to later

## Priority Definitions
- **Critical**: Must pass before release, core functionality
- **High**: Important features, should pass before release
- **Medium**: Nice to have, can be addressed in next iteration
- **Low**: Minor features, can be addressed later

Open `test-checklist.csv` to start tracking your test execution progress!
