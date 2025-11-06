# Troubleshooting Dashboard Announcements

## Step-by-Step Debugging Guide

### Step 1: Create Sample Data
1. Visit: `quick_setup_announcements.php`
2. This will create the announcements table and insert sample data
3. Should show "✅ Sample Announcements Created Successfully!"

### Step 2: Debug the System
1. Visit: `debug_announcements.php` 
2. This will show:
   - Database connection status
   - Table existence
   - Count of announcements
   - Sample query results

### Step 3: Test Dashboard with Debug Mode
1. Visit: `dashboard.php?debug=1`
2. This will show a debug message with announcement count at the top
3. You should see: "Debug: Found X announcements for user role: Admin"

### Step 4: Check Dashboard Display
1. Visit: `dashboard.php` (without debug)
2. You should now see the Announcements section with:
   - Header with "Announcements" title
   - "Create" button (for Admin/Staff/Faculty)
   - List of announcements OR "No Announcements Yet" message

## Expected Results

### If No Announcements:
- Should show empty state with megaphone icon
- "No Announcements Yet" message
- "Create First Announcement" button for authorized users

### If Announcements Exist:
- Should show up to 10 announcements
- Priority badges (Urgent=red, High=orange, Medium=blue, Low=gray)
- Target audience badges
- Management dropdown for authorized users

## Common Issues & Solutions

### Issue: "No Announcements Yet" even after creating samples
**Solution**: Check if announcements table exists and has data
- Run `quick_setup_announcements.php`
- Check `debug_announcements.php` for detailed info

### Issue: Create button not showing
**Solution**: Check user role permissions
- Admin, Administrative Staff, and Faculty should see Create button
- Students should see read-only view

### Issue: Empty announcements section
**Solution**: Database connection or table issues
- Check `debug_announcements.php` for database errors
- Verify announcements table exists with proper columns

## Files to Test In Order:
1. `test_db_connection.php` - Test database connection
2. `quick_setup_announcements.php` - Creates data  
3. `debug_announcements.php` - Verifies system
4. `dashboard.php?debug=1` - Tests with debug info
5. `dashboard.php` - Final result

## Fixed Issues:
- ✅ Fixed "Call to undefined function getDB()" error
- ✅ Updated all files to use global $pdo connection
- ✅ Announcements section now always shows (even when empty)

If you're still not seeing the announcements section after following these steps, please run the debug scripts and share the output!