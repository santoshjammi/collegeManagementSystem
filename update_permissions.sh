#!/bin/bash

# Script to update role-based permissions across all PHP files
# This script updates the old role names to new ones and adds permission checks

echo "Updating role-based permissions across all PHP files..."

# List of files to update
files=(
    "subjects.php"
    "tests.php"
    "grades.php"
    "fees.php"
    "library.php"
    "placements.php"
    "student-grades.php"
    "admissions.php"
)

# Update navigation menus
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "Updating $file..."

        # Update role checks in navigation
        sed -i 's/hasRole(\['\''Management'\'', '\''Admin'\''\])/hasRole(['\''Administrative Staff'\'', '\''Admin'\''])/g' "$file"
        sed -i 's/hasRole(\['\''Management'\'', '\''Admin'\'', '\''Faculty'\''\])/canAccessModule('\''subjects'\'') || canAccessModule('\''tests'\'') || canAccessModule('\''grades'\'')/g' "$file"

        # Update action buttons with permission checks
        # Add write permissions to action buttons
        sed -i 's/<button class="btn btn-primary" data-bs-toggle="modal"/<?php if (canWrite('\''subjects'\'')): ?><button class="btn btn-primary" data-bs-toggle="modal"/g' "$file"
        sed -i 's/<button class="btn btn-sm btn-outline-primary/<button class="btn btn-sm btn-outline-primary/g' "$file"
        sed -i 's/<\/button>/<\/button><?php endif; ?>/g' "$file"

        echo "Updated $file"
    fi
done

echo "Role-based permission updates completed!"