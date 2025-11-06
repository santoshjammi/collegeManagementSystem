#!/bin/bash

# Script to organize College Management System files
# Moves supporting files to supporting_ones directory

echo "🗂️  College Management System File Organization"
echo "==============================================="

# Create supporting_ones directory if it doesn't exist
mkdir -p supporting_ones

echo "📁 Moving supporting files to supporting_ones directory..."

# Documentation files
echo "Moving documentation files..."
mv ANNOUNCEMENTS_README.md supporting_ones/ 2>/dev/null
mv DASHBOARD_ANNOUNCEMENTS.md supporting_ones/ 2>/dev/null
mv DASHBOARD_ANNOUNCEMENTS_FINAL.md supporting_ones/ 2>/dev/null
mv TROUBLESHOOTING_ANNOUNCEMENTS.md supporting_ones/ 2>/dev/null

# Setup and utility scripts
echo "Moving setup and utility scripts..."
mv add_sample_papers.php supporting_ones/ 2>/dev/null
mv add_subjects_data.php supporting_ones/ 2>/dev/null
mv create_sample_announcements.php supporting_ones/ 2>/dev/null
mv create_test_users.php supporting_ones/ 2>/dev/null
mv fix_roles.php supporting_ones/ 2>/dev/null
mv migrate_subjects_and_add_communication.php supporting_ones/ 2>/dev/null
mv quick_setup_announcements.php supporting_ones/ 2>/dev/null
mv setup_users.php supporting_ones/ 2>/dev/null

# Debug files
echo "Moving debug files..."
mv debug_announcements.php supporting_ones/ 2>/dev/null
mv debug_db.php supporting_ones/ 2>/dev/null
mv debug_login.php supporting_ones/ 2>/dev/null
mv debug_papers.php supporting_ones/ 2>/dev/null
mv debug_subjects.php supporting_ones/ 2>/dev/null
mv simple_debug.php supporting_ones/ 2>/dev/null
mv simple_test.php supporting_ones/ 2>/dev/null

# Test files
echo "Moving test files..."
mv test_alice_login.php supporting_ones/ 2>/dev/null
mv test_announcements.php supporting_ones/ 2>/dev/null
mv test_db.php supporting_ones/ 2>/dev/null
mv test_db_connection.php supporting_ones/ 2>/dev/null
mv test_full_login_flow.php supporting_ones/ 2>/dev/null
mv test_login.php supporting_ones/ 2>/dev/null
mv test_login_process.php supporting_ones/ 2>/dev/null
mv test_query.php supporting_ones/ 2>/dev/null
mv test_table.php supporting_ones/ 2>/dev/null

# Check files
echo "Moving check files..."
mv check_counts.php supporting_ones/ 2>/dev/null
mv check_papers.php supporting_ones/ 2>/dev/null
mv check_subjects.php supporting_ones/ 2>/dev/null
mv check_table.php supporting_ones/ 2>/dev/null
mv check_users.php supporting_ones/ 2>/dev/null

# Legacy files
echo "Moving legacy files..."
mv announcements.php supporting_ones/ 2>/dev/null
mv login_test.html supporting_ones/ 2>/dev/null
mv login_test.php supporting_ones/ 2>/dev/null
mv verify_setup.php supporting_ones/ 2>/dev/null

echo ""
echo "✅ File organization complete!"
echo ""
echo "📊 Current main directory structure:"
ls -la | grep -E '\.(php|html)$' | grep -v supporting_ones

echo ""
echo "📁 Files moved to supporting_ones/:"
ls -la supporting_ones/ | wc -l | awk '{print $1-3 " files moved"}'

echo ""
echo "🎯 Main directory now contains only essential operational files!"
echo "📁 All development/testing/setup utilities are in supporting_ones/"