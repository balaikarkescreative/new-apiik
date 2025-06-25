# Test Scenarios for ICV Management System

## Overview
This document outlines comprehensive test scenarios for the ICV (International Certificate of Vaccination) Management System built with Laravel and Filament. The system manages the complete lifecycle of ICV certificates from application to distribution.

## Test Environment Setup
- Database: SQLite (for testing) / MySQL (for production)
- Framework: Laravel with Filament admin panel
- Authentication: Role-based access control
- Email: SMTP configuration required for notification testing

---

## 1. Authentication & Authorization Tests

### 1.1 User Login Tests
**Test ID: AUTH-001**
- **Scenario**: Valid user login with active status
- **Steps**:
  1. Navigate to login page
  2. Enter valid username and password for active user
  3. Click login button
- **Expected Result**: User successfully logged in and redirected to dashboard
- **Test Data**: Active user credentials

**Test ID: AUTH-002**
- **Scenario**: Login attempt with inactive user
- **Steps**:
  1. Navigate to login page
  2. Enter valid credentials for inactive user (status: "tidak aktif")
  3. Click login button
- **Expected Result**: Login rejected with appropriate error message
- **Test Data**: Inactive user credentials

**Test ID: AUTH-003**
- **Scenario**: Invalid credentials login attempt
- **Steps**:
  1. Navigate to login page
  2. Enter invalid username/password combination
  3. Click login button
- **Expected Result**: Login failed with error message
- **Test Data**: Non-existent or wrong credentials

### 1.2 Role-Based Access Control Tests
**Test ID: RBAC-001**
- **Scenario**: Admin access to all resources
- **Steps**:
  1. Login as admin user
  2. Navigate to ICV management section
  3. Attempt to access all CRUD operations
- **Expected Result**: Admin has full access to all operations
- **Test Data**: Admin role user

**Test ID: RBAC-002**
- **Scenario**: Regular user restricted access
- **Steps**:
  1. Login as regular user
  2. Navigate to ICV management section
  3. Attempt to access admin-only operations
- **Expected Result**: User sees only permitted actions, restricted from admin operations
- **Test Data**: Regular user role

---

## 2. User Management Tests

### 2.1 User Creation Tests
**Test ID: USER-001**
- **Scenario**: Create new user with unique username
- **Steps**:
  1. Login as admin
  2. Navigate to User Resource
  3. Click "Create User"
  4. Fill form with valid data
  5. Submit form
- **Expected Result**: User created successfully with unique username
- **Test Data**: Valid user data with unique email/username

**Test ID: USER-002**
- **Scenario**: Username generation and uniqueness
- **Steps**:
  1. Create user with name "John Doe"
  2. Verify username generated as "john.doe"
  3. Create another user with same name
  4. Verify second username generated as "john.doe1"
- **Expected Result**: Automatic unique username generation works correctly
- **Test Data**: Duplicate names for testing uniqueness

**Test ID: USER-003**
- **Scenario**: Duplicate username validation
- **Steps**:
  1. Attempt to create user with existing username
  2. Submit form
- **Expected Result**: Validation error displayed for duplicate username
- **Test Data**: Existing username

### 2.2 Username Reset Tests
**Test ID: USER-004**
- **Scenario**: Username reset functionality
- **Steps**:
  1. Select existing user for username reset
  2. Execute reset username action
  3. Verify new username generated
- **Expected Result**: Username regenerated based on current name, excluding current user from duplicate check
- **Test Data**: Existing user with modifiable name

---

## 3. ICV Management Tests

### 3.1 ICV Application Tests
**Test ID: ICV-001**
- **Scenario**: Create new ICV application
- **Steps**:
  1. Login as user
  2. Navigate to ICV Resource
  3. Click "Create ICV"
  4. Fill application form with required data
  5. Submit application
- **Expected Result**: ICV application created with initial status
- **Test Data**: Complete ICV application data

**Test ID: ICV-002**
- **Scenario**: ICV application validation
- **Steps**:
  1. Attempt to create ICV with missing required fields
  2. Submit form
- **Expected Result**: Validation errors displayed for required fields
- **Test Data**: Incomplete form data

### 3.2 ICV Verification Process Tests
**Test ID: ICV-003**
- **Scenario**: Document verification process
- **Steps**:
  1. Login as admin
  2. Navigate to ICV verification page
  3. Select pending ICV application
  4. Review documents and verify
  5. Update status to verified
- **Expected Result**: ICV status updated, verification email sent
- **Test Data**: Pending ICV application

**Test ID: ICV-004**
- **Scenario**: Document rejection process
- **Steps**:
  1. Login as admin
  2. Navigate to ICV verification page
  3. Select pending ICV application
  4. Reject with reason
- **Expected Result**: ICV status updated to rejected, notification sent
- **Test Data**: Rejectable ICV application

### 3.3 ICV Allocation Tests
**Test ID: ICV-005**
- **Scenario**: ICV allocation process
- **Steps**:
  1. Login as admin
  2. Navigate to verified ICVs needing allocation
  3. Assign ICV numbers to verified applications
  4. Confirm allocation
- **Expected Result**: ICV numbers assigned, allocation email sent
- **Test Data**: Verified ICV applications

### 3.4 Billing Generation Tests
**Test ID: ICV-006**
- **Scenario**: Generate billing for allocated ICVs
- **Steps**:
  1. Login as admin
  2. Navigate to allocated ICVs without billing
  3. Generate billing codes
  4. Confirm billing creation
- **Expected Result**: Billing codes generated, billing notification sent
- **Test Data**: Allocated ICVs

### 3.5 Schedule Distribution Tests
**Test ID: ICV-007**
- **Scenario**: Schedule ICV distribution
- **Steps**:
  1. Login as admin
  2. Navigate to paid ICVs needing scheduling
  3. Set distribution schedule
  4. Confirm scheduling
- **Expected Result**: Distribution scheduled, schedule notification sent
- **Test Data**: Paid ICV applications

---

## 4. Email Notification Tests

### 4.1 Verification Notification Tests
**Test ID: EMAIL-001**
- **Scenario**: Verification email notification
- **Steps**:
  1. Complete ICV verification process
  2. Check email delivery
  3. Verify email content and formatting
- **Expected Result**: Email sent with correct content, no null property errors
- **Test Data**: ICV with complete user data

**Test ID: EMAIL-002**
- **Scenario**: Verification email with incomplete user data
- **Steps**:
  1. Process ICV with user having null fields
  2. Trigger verification email
- **Expected Result**: Email sent successfully with null-safe handling
- **Test Data**: ICV with user having null optional fields

### 4.2 Allocation Notification Tests
**Test ID: EMAIL-003**
- **Scenario**: Allocation email notification
- **Steps**:
  1. Complete ICV allocation process
  2. Verify email sent to user
  3. Check email contains allocation details
- **Expected Result**: Email delivered with allocation information
- **Test Data**: Allocated ICV

### 4.3 Billing Notification Tests
**Test ID: EMAIL-004**
- **Scenario**: Billing email notification
- **Steps**:
  1. Generate billing for ICV
  2. Verify billing email sent
  3. Check email contains billing code and payment instructions
- **Expected Result**: Email sent with billing details
- **Test Data**: ICV with generated billing

### 4.4 Schedule Notification Tests
**Test ID: EMAIL-005**
- **Scenario**: Schedule email notification
- **Steps**:
  1. Schedule ICV distribution
  2. Verify schedule email sent
  3. Check email contains distribution schedule details
- **Expected Result**: Email sent with schedule information
- **Test Data**: Scheduled ICV

### 4.5 SBBK Notification Tests
**Test ID: EMAIL-006**
- **Scenario**: SBBK (Certificate) notification
- **Steps**:
  1. Complete SBBK upload/processing
  2. Verify SBBK notification email sent
  3. Check email content
- **Expected Result**: Email sent with SBBK information
- **Test Data**: ICV with SBBK data

---

## 5. Data Management & Query Tests

### 5.1 Table Ordering Tests
**Test ID: DATA-001**
- **Scenario**: ICV table ordering prioritization
- **Steps**:
  1. Navigate to ICV list
  2. Verify records needing action appear first
  3. Check "belum dijadwalkan" items are prioritized
- **Expected Result**: Records requiring action displayed first
- **Test Data**: Mixed status ICV records

**Test ID: DATA-002**
- **Scenario**: Billing table ordering
- **Steps**:
  1. Navigate to billing list
  2. Verify "belum ada billing" records appear first
  3. Check proper ordering by priority
- **Expected Result**: Unbilled records prioritized
- **Test Data**: Mixed billing status records

**Test ID: DATA-003**
- **Scenario**: SBBK upload prioritization
- **Steps**:
  1. Navigate to SBBK management
  2. Verify "belum upload SBBK" records appear first
  3. Check ordering logic
- **Expected Result**: Records without SBBK upload prioritized
- **Test Data**: Mixed SBBK upload status

### 5.2 Eager Loading Tests
**Test ID: DATA-004**
- **Scenario**: Efficient data loading with relationships
- **Steps**:
  1. Access ICV list with user relationships
  2. Monitor database queries
  3. Verify no N+1 query problems
- **Expected Result**: Efficient loading with eager loaded relationships
- **Test Data**: ICVs with associated users

---

## 6. Workflow Integration Tests

### 6.1 Complete ICV Lifecycle Test
**Test ID: WORKFLOW-001**
- **Scenario**: End-to-end ICV processing
- **Steps**:
  1. Create ICV application as user
  2. Verify documents as admin
  3. Allocate ICV numbers
  4. Generate billing
  5. Process payment (simulate)
  6. Schedule distribution
  7. Complete distribution
- **Expected Result**: Complete workflow with notifications at each step
- **Test Data**: Complete ICV application data

### 6.2 Rejection and Resubmission Workflow
**Test ID: WORKFLOW-002**
- **Scenario**: Document rejection and resubmission process
- **Steps**:
  1. Submit ICV application
  2. Reject during verification
  3. Resubmit corrected application
  4. Approve and continue workflow
- **Expected Result**: Proper handling of rejection-resubmission cycle
- **Test Data**: Initially incomplete/incorrect application

---

## 7. Error Handling & Edge Cases

### 7.1 Null Data Handling Tests
**Test ID: ERROR-001**
- **Scenario**: Null-safe email notification handling
- **Steps**:
  1. Create ICV with user having null optional fields
  2. Trigger various email notifications
  3. Verify no fatal errors occur
- **Expected Result**: Graceful handling of null data in emails
- **Test Data**: Users with null fields

### 7.2 Static Method Usage Tests
**Test ID: ERROR-002**
- **Scenario**: Username generation static method calls
- **Steps**:
  1. Trigger username generation in various contexts
  2. Verify no "Using $this when not in object context" errors
  3. Test both creation and reset scenarios
- **Expected Result**: Proper static method usage throughout
- **Test Data**: Various username generation scenarios

### 7.3 Concurrency Tests
**Test ID: ERROR-003**
- **Scenario**: Concurrent username generation
- **Steps**:
  1. Simulate multiple users creating accounts simultaneously
  2. Verify unique username generation under load
  3. Check for race conditions
- **Expected Result**: No duplicate usernames generated
- **Test Data**: Multiple concurrent user creation requests

---

## 8. Performance Tests

### 8.1 Large Dataset Tests
**Test ID: PERF-001**
- **Scenario**: Performance with large number of ICVs
- **Steps**:
  1. Create 1000+ ICV records
  2. Test list loading performance
  3. Test search and filter performance
- **Expected Result**: Acceptable performance with pagination
- **Test Data**: Large dataset of ICV records

### 8.2 Email Queue Performance
**Test ID: PERF-002**
- **Scenario**: Bulk email notification performance
- **Steps**:
  1. Process 100+ ICVs simultaneously
  2. Monitor email queue processing
  3. Verify no timeouts or failures
- **Expected Result**: Efficient email queue processing
- **Test Data**: Bulk ICV processing scenario

---

## 9. Security Tests

### 9.1 Access Control Tests
**Test ID: SEC-001**
- **Scenario**: Unauthorized access prevention
- **Steps**:
  1. Attempt direct URL access to admin functions as regular user
  2. Try to access other users' data
  3. Test API endpoint security
- **Expected Result**: Access denied for unauthorized operations
- **Test Data**: Different user roles and restricted URLs

### 9.2 Data Validation Tests
**Test ID: SEC-002**
- **Scenario**: Input validation and sanitization
- **Steps**:
  1. Submit forms with malicious input
  2. Test SQL injection attempts
  3. Test XSS prevention
- **Expected Result**: Malicious input rejected, data sanitized
- **Test Data**: Various malicious input patterns

---

## 10. Migration & Maintenance Tests

### 10.1 User Status Migration Tests
**Test ID: MIG-001**
- **Scenario**: User status enum migration
- **Steps**:
  1. Run user status migration
  2. Verify existing users converted to "aktif"/"tidak aktif"
  3. Test login with migrated users
- **Expected Result**: Successful migration with functional authentication
- **Test Data**: Users with old status values

### 10.2 Database Backup & Restore Tests
**Test ID: MIG-002**
- **Scenario**: Data backup and restoration
- **Steps**:
  1. Create backup of database with test data
  2. Perform restoration
  3. Verify data integrity
- **Expected Result**: Complete data restoration without corruption
- **Test Data**: Representative dataset

---

## 11. Izin (License Application) Management Tests

### 11.1 Izin Application Creation Tests
**Test ID: IZIN-001**
- **Scenario**: Create new Izin application
- **Steps**:
  1. Login as user
  2. Navigate to Izin Resource
  3. Click "Create Izin"
  4. Fill application form with required data (kategori_izin, files, etc.)
  5. Submit application
- **Expected Result**: Izin application created with initial status, creation email sent
- **Test Data**: Complete Izin application data

**Test ID: IZIN-002**
- **Scenario**: Izin application validation
- **Steps**:
  1. Attempt to create Izin with missing required fields
  2. Submit form
- **Expected Result**: Validation errors displayed for required fields
- **Test Data**: Incomplete form data

**Test ID: IZIN-003**
- **Scenario**: Izin Baru restriction for existing applications
- **Steps**:
  1. Create "Izin Baru" application as user
  2. Attempt to create another "Izin Baru" before first one is completed
  3. Check creation restriction
- **Expected Result**: User prevented from creating multiple "Izin Baru" until first is completed
- **Test Data**: User with existing "Izin Baru" application

**Test ID: IZIN-004**
- **Scenario**: File upload with custom naming
- **Steps**:
  1. Upload documents during Izin creation
  2. Verify files are renamed with custom format
  3. Check filename includes document type, username, and timestamp
- **Expected Result**: Files uploaded with custom naming convention
- **Test Data**: Various document types and file formats

### 11.2 Izin Verification Process Tests
**Test ID: IZIN-005**
- **Scenario**: Izin document verification
- **Steps**:
  1. Login as admin/verifikator
  2. Navigate to Izin verification page
  3. Select pending Izin application
  4. Review documents and verify
  5. Update status to verified
- **Expected Result**: Izin status updated, verification email sent
- **Test Data**: Pending Izin application

**Test ID: IZIN-006**
- **Scenario**: Izin document rejection
- **Steps**:
  1. Login as admin/verifikator
  2. Navigate to Izin verification page
  3. Select pending Izin application
  4. Reject with reason
- **Expected Result**: Izin status updated to rejected, rejection notification sent
- **Test Data**: Rejectable Izin application

### 11.3 Izin Approval Process Tests
**Test ID: IZIN-007**
- **Scenario**: Surat Persetujuan (Approval Letter) generation
- **Steps**:
  1. Login as admin
  2. Navigate to verified Izin applications
  3. Generate approval letter
  4. Confirm approval letter creation
- **Expected Result**: Approval letter generated, approval email sent
- **Test Data**: Verified Izin applications

**Test ID: IZIN-008**
- **Scenario**: PKS (Agreement) document processing
- **Steps**:
  1. Process approved Izin for PKS generation
  2. Upload PKS documents
  3. Complete PKS workflow
- **Expected Result**: PKS documents processed, PKS notification sent
- **Test Data**: Approved Izin applications

### 11.4 Izin Visitasi Tests
**Test ID: IZIN-009**
- **Scenario**: Visitasi (Site Visit) scheduling
- **Steps**:
  1. Login as admin
  2. Navigate to Izin requiring visitasi
  3. Schedule visitasi
  4. Confirm visitasi scheduling
- **Expected Result**: Visitasi scheduled, visitasi notification sent
- **Test Data**: Izin applications requiring site visits

**Test ID: IZIN-010**
- **Scenario**: Visitasi completion and reporting
- **Steps**:
  1. Complete visitasi process
  2. Upload visitasi report
  3. Update visitasi status
- **Expected Result**: Visitasi completed, report uploaded successfully
- **Test Data**: Scheduled visitasi

### 11.5 Izin Extension Tests
**Test ID: IZIN-011**
- **Scenario**: Izin extension application
- **Steps**:
  1. Login as user with completed Izin
  2. Apply for Izin extension
  3. Fill extension form
  4. Submit extension application
- **Expected Result**: Extension application created and processed
- **Test Data**: User with completed Izin eligible for extension

**Test ID: IZIN-012**
- **Scenario**: Extension validation and approval
- **Steps**:
  1. Process extension application as admin
  2. Verify extension requirements
  3. Approve or reject extension
- **Expected Result**: Extension processed with appropriate notifications
- **Test Data**: Extension applications

### 11.6 Izin Status Workflow Tests
**Test ID: IZIN-013**
- **Scenario**: Complete Izin lifecycle workflow
- **Steps**:
  1. Create Izin application as user
  2. Verify documents as admin
  3. Generate approval letter
  4. Process PKS agreement
  5. Schedule and complete visitasi (if required)
  6. Finalize Izin approval
- **Expected Result**: Complete workflow with notifications at each step
- **Test Data**: Complete Izin application data

**Test ID: IZIN-014**
- **Scenario**: Izin workflow with rejection and resubmission
- **Steps**:
  1. Submit Izin application
  2. Reject during verification
  3. Resubmit corrected application
  4. Continue workflow to completion
- **Expected Result**: Proper handling of rejection-resubmission cycle
- **Test Data**: Initially incomplete/incorrect application

### 11.7 Izin Email Notification Tests
**Test ID: IZIN-015**
- **Scenario**: Izin creation notification
- **Steps**:
  1. Create new Izin application
  2. Verify creation email sent
  3. Check email content and formatting
- **Expected Result**: Email sent with application details
- **Test Data**: New Izin application

**Test ID: IZIN-016**
- **Scenario**: Izin verification notification
- **Steps**:
  1. Complete Izin verification process
  2. Verify verification email sent
  3. Check email contains verification status
- **Expected Result**: Email sent with verification details
- **Test Data**: Verified Izin application

**Test ID: IZIN-017**
- **Scenario**: Izin approval notification
- **Steps**:
  1. Generate approval letter for Izin
  2. Verify approval email sent
  3. Check email contains approval details
- **Expected Result**: Email sent with approval information
- **Test Data**: Approved Izin application

**Test ID: IZIN-018**
- **Scenario**: PKS notification
- **Steps**:
  1. Process PKS documents
  2. Verify PKS email sent
  3. Check email contains PKS information
- **Expected Result**: Email sent with PKS details
- **Test Data**: Izin with PKS processing

**Test ID: IZIN-019**
- **Scenario**: Visitasi notification
- **Steps**:
  1. Schedule visitasi for Izin
  2. Verify visitasi email sent
  3. Check email contains visitasi schedule
- **Expected Result**: Email sent with visitasi information
- **Test Data**: Izin with scheduled visitasi

### 11.8 Izin Access Control Tests
**Test ID: IZIN-020**
- **Scenario**: User access to own Izin applications
- **Steps**:
  1. Login as regular user
  2. Navigate to Izin list
  3. Verify user sees only their own applications
- **Expected Result**: User restricted to their own Izin applications
- **Test Data**: Multiple users with Izin applications

**Test ID: IZIN-021**
- **Scenario**: Admin access to all Izin applications
- **Steps**:
  1. Login as admin
  2. Navigate to Izin management
  3. Verify admin can see and manage all applications
- **Expected Result**: Admin has access to all Izin applications
- **Test Data**: Admin user with multiple Izin applications

**Test ID: IZIN-022**
- **Scenario**: Verifikator role access control
- **Steps**:
  1. Login as verifikator
  2. Navigate to verification pages
  3. Verify appropriate access levels
- **Expected Result**: Verifikator has access to verification functions only
- **Test Data**: Verifikator role user

### 11.9 Izin Data Management Tests
**Test ID: IZIN-023**
- **Scenario**: Izin table ordering and filtering
- **Steps**:
  1. Navigate to Izin list
  2. Verify records needing action appear first
  3. Test filtering by status, category, user
- **Expected Result**: Proper ordering and filtering functionality
- **Test Data**: Mixed status Izin records

**Test ID: IZIN-024**
- **Scenario**: Izin search functionality
- **Steps**:
  1. Search Izin by various criteria
  2. Test search by user name, status, category
  3. Verify search results accuracy
- **Expected Result**: Search returns accurate results
- **Test Data**: Diverse Izin records for searching

---

## Test Data Requirements

### User Test Data
- Admin user: username "admin", password "password123", status "aktif"
- Regular user: username "user1", password "password123", status "aktif"
- Inactive user: username "inactive", password "password123", status "tidak aktif"
- Users with null optional fields for testing null-safe operations

### ICV Test Data
- Valid ICV applications with complete documentation
- Invalid ICV applications missing required fields
- ICVs in various status states (pending, verified, allocated, billed, scheduled, distributed)

### Izin Test Data
- Valid Izin applications with complete documentation (file1-file14)
- Invalid Izin applications missing required fields
- Izin applications in various categories (Izin Baru, Extension, etc.)
- Izin applications in various status states (pending, verified, approved, rejected)
- Sample document files for upload testing
- Users with existing Izin applications for testing restrictions
- PKS documents for agreement processing
- Visitasi scheduling and completion data

### Email Configuration
- Valid SMTP settings for email notification testing
- Test email addresses for receiving notifications

---

## Test Execution Notes

1. **Environment Setup**: Ensure clean database state before each test suite
2. **Email Testing**: Use email testing tools like MailHog for development
3. **File Uploads**: Prepare sample documents for ICV applications
4. **Performance Testing**: Use appropriate tools for load testing
5. **Security Testing**: Follow OWASP guidelines for web application security testing

## Expected Test Coverage
- Unit Tests: 80%+ code coverage
- Integration Tests: All major workflows covered
- E2E Tests: Critical user journeys validated
- Performance Tests: Response times within acceptable limits
- Security Tests: All OWASP Top 10 vulnerabilities addressed

This comprehensive test plan ensures the ICV Management System functions correctly across all features, handles edge cases gracefully, and maintains security and performance standards.
