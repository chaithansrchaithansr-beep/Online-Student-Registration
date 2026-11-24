/**
 * ============================================================================
 * validation.js - Pure JavaScript Field Validation Rules
 * ============================================================================
 * Provides reusable validation functions for every registration form field.
 * Reads the configurable USN regex and allowed options injected from config.php
 * via window.REGISTRATION_CONFIG so client and server rules stay synchronized.
 */

(function (window) {
    'use strict';

    const config = window.REGISTRATION_CONFIG || {};

    const DEFAULT_USN_PATTERN = '^[1-4][A-Z]{2}[0-9]{2}[A-Z]{2,3}[0-9]{3}$';
    const DEFAULT_USN_HINT = 'Format: 1 digit (1-4) + 2 letters + 2 digits + 2-3 letters + 3 digits (e.g., 1RV23CS045)';
    const DEFAULT_DEPARTMENTS = ['CSE', 'ISE', 'AIML', 'ECE', 'EEE', 'ME', 'CIVIL'];
    const DEFAULT_SEMESTERS = [
        '1st Semester', '2nd Semester', '3rd Semester', '4th Semester',
        '5th Semester', '6th Semester', '7th Semester', '8th Semester'
    ];

    const usnRegex = new RegExp(config.usnPattern || DEFAULT_USN_PATTERN);
    const usnHint = config.usnHint || DEFAULT_USN_HINT;
    const allowedDepartments = Array.isArray(config.allowedDepartments) ? config.allowedDepartments : DEFAULT_DEPARTMENTS;
    const allowedSemesters = Array.isArray(config.allowedSemesters) ? config.allowedSemesters : DEFAULT_SEMESTERS;

    /**
     * Validates Full Name:
     * - Required
     * - Only letters, spaces, periods, apostrophes, or hyphens
     * - Minimum 3 alphabetic letters
     */
    function validateFullName(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Full name is required.' };
        }

        if (!/^[A-Za-z.\s'-]+$/.test(value)) {
            return {
                valid: false,
                message: 'Name may only contain letters, spaces, dots (.), hyphens (-), and apostrophes (\').'
            };
        }

        const letterMatches = value.match(/[A-Za-z]/g) || [];
        if (letterMatches.length < 3) {
            return { valid: false, message: 'Full name must contain at least 3 letters.' };
        }

        if (value.length > 100) {
            return { valid: false, message: 'Full name must not exceed 100 characters.' };
        }

        return { valid: true, message: 'Valid full name.' };
    }

    /**
     * Validates USN (University Seat Number):
     * - Required
     * - Matches configurable college USN regex (case-insensitive input normalized to uppercase)
     */
    function validateUSN(rawValue) {
        const value = String(rawValue || '').trim().toUpperCase();

        if (!value) {
            return { valid: false, message: 'USN is required.' };
        }

        if (!usnRegex.test(value)) {
            return { valid: false, message: 'Invalid USN. ' + usnHint };
        }

        return { valid: true, message: 'Valid USN format.' };
    }

    /**
     * Validates Email Address:
     * - Required
     * - Valid standard email syntax without consecutive dots
     */
    function validateEmail(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Email address is required.' };
        }

        const emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
        if (!emailPattern.test(value) || value.includes('..')) {
            return { valid: false, message: 'Please enter a valid email address (e.g., student@college.edu).' };
        }

        if (value.length > 120) {
            return { valid: false, message: 'Email address must not exceed 120 characters.' };
        }

        return { valid: true, message: 'Valid email address.' };
    }

    /**
     * Validates Phone Number:
     * - Required
     * - Must contain exactly 10 digits
     */
    function validatePhone(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Phone number is required.' };
        }

        if (!/^\d{10}$/.test(value)) {
            return { valid: false, message: 'Phone number must contain exactly 10 digits (numbers only).' };
        }

        return { valid: true, message: 'Valid 10-digit phone number.' };
    }

    /**
     * Validates Department dropdown selection.
     */
    function validateDepartment(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Please select a department.' };
        }

        if (!allowedDepartments.includes(value)) {
            return { valid: false, message: 'Selected department is invalid.' };
        }

        return { valid: true, message: 'Department selected.' };
    }

    /**
     * Validates Semester dropdown selection.
     */
    function validateSemester(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Please select a semester.' };
        }

        if (!allowedSemesters.includes(value)) {
            return { valid: false, message: 'Selected semester is invalid.' };
        }

        return { valid: true, message: 'Semester selected.' };
    }

    /**
     * Validates Complete Address:
     * - Required
     * - Minimum 10 characters
     */
    function validateAddress(rawValue) {
        const value = String(rawValue || '').trim();

        if (!value) {
            return { valid: false, message: 'Complete address is required.' };
        }

        if (value.length < 10) {
            return {
                valid: false,
                message: 'Address must be at least 10 characters long (' + value.length + '/10).'
            };
        }

        if (value.length > 500) {
            return { valid: false, message: 'Address must not exceed 500 characters.' };
        }

        return { valid: true, message: 'Valid address.' };
    }

    /**
     * Dispatches validation for a single named field.
     */
    function validateField(fieldName, value) {
        switch (fieldName) {
            case 'full_name':
                return validateFullName(value);
            case 'usn':
                return validateUSN(value);
            case 'email':
                return validateEmail(value);
            case 'phone':
                return validatePhone(value);
            case 'department':
                return validateDepartment(value);
            case 'semester':
                return validateSemester(value);
            case 'address':
                return validateAddress(value);
            default:
                return { valid: true, message: '' };
        }
    }

    /**
     * Validates all form fields and returns overall status plus per-field results.
     */
    function validateAllFields(values) {
        const fields = ['full_name', 'usn', 'email', 'phone', 'department', 'semester', 'address'];
        const results = {};
        let isValid = true;

        fields.forEach(function (field) {
            const result = validateField(field, values[field]);
            results[field] = result;
            if (!result.valid) {
                isValid = false;
            }
        });

        return { isValid: isValid, results: results };
    }

    window.StudentValidator = {
        validateFullName: validateFullName,
        validateUSN: validateUSN,
        validateEmail: validateEmail,
        validatePhone: validatePhone,
        validateDepartment: validateDepartment,
        validateSemester: validateSemester,
        validateAddress: validateAddress,
        validateField: validateField,
        validateAllFields: validateAllFields
    };
})(window);
