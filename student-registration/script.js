/**
 * ============================================================================
 * script.js - jQuery Interactive Form Validation & User Experience
 * ============================================================================
 * Uses jQuery to provide real-time field validation, green success feedback,
 * red error feedback, USN uppercase normalization, live address character
 * counting, smooth scroll-to-error on submit, and complete form reset.
 */

$(document).ready(function () {
    'use strict';

    const $form = $('#registrationForm');
    if ($form.length === 0) {
        return;
    }

    const $clientErrorBanner = $('#clientErrorBanner');
    const $serverErrorBanner = $('#serverErrorBanner');
    const $clientErrorSummary = $('#clientErrorSummary');
    const $address = $('#address');
    const $addressCounter = $('#addressCounter');
    const $submitBtn = $('#submitBtn');
    const $resetBtn = $('#resetBtn');

    const fieldNames = ['full_name', 'usn', 'email', 'phone', 'department', 'semester', 'address'];

    /**
     * Updates the visual state (border, status icon, and message) of a field.
     */
    function applyFieldUI(fieldName, result) {
        const $input = $('#' + fieldName);
        const $feedback = $('#' + fieldName + '_feedback');

        $input.removeClass('is-valid is-invalid');
        $feedback.removeClass('error-message success-message visible');

        if (result.valid) {
            $input.addClass('is-valid');
            $feedback
                .addClass('success-message visible')
                .text(result.message);
        } else {
            $input.addClass('is-invalid');
            $feedback
                .addClass('error-message visible')
                .text(result.message);
        }
    }

    /**
     * Updates the live character counter above the Address textarea.
     */
    function updateAddressCounter() {
        const len = $.trim($address.val()).length;
        $addressCounter.text(len + ' / 10 min chars');

        if (len >= 10) {
            $addressCounter.addClass('met');
        } else {
            $addressCounter.removeClass('met');
        }
    }

    /**
     * Auto-uppercase USN input while preserving cursor position.
     */
    $('#usn').on('input', function () {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        const upper = $(this).val().toUpperCase();
        if ($(this).val() !== upper) {
            $(this).val(upper);
            if (typeof start === 'number' && typeof end === 'number') {
                this.setSelectionRange(start, end);
            }
        }
    });

    /**
     * Real-time validation on input, blur, and dropdown change.
     */
    $.each(fieldNames, function (_, fieldName) {
        const $field = $('#' + fieldName);
        const isSelect = $field.is('select');

        $field.on(isSelect ? 'change blur' : 'input blur', function (event) {
            const rawVal = $(this).val();

            // On initial typing in an empty non-blurred input, avoid flashing red immediately
            // unless the user already interacted and left it invalid or submitted once.
            if (event.type === 'input' && $.trim(rawVal) === '' && !$field.hasClass('is-invalid') && !$field.hasClass('is-valid')) {
                if (fieldName === 'address') {
                    updateAddressCounter();
                }
                return;
            }

            if (fieldName === 'address') {
                updateAddressCounter();
            }

            const result = window.StudentValidator.validateField(fieldName, rawVal);
            applyFieldUI(fieldName, result);

            // Hide top client summary banner once all fields become valid
            if ($clientErrorBanner.is(':visible')) {
                const currentValues = collectFormValues();
                if (window.StudentValidator.validateAllFields(currentValues).isValid) {
                    $clientErrorBanner.addClass('hidden');
                }
            }
        });
    });

    /**
     * Collects current values of all form fields.
     */
    function collectFormValues() {
        const values = {};
        $.each(fieldNames, function (_, fieldName) {
            values[fieldName] = $('#' + fieldName).val();
        });
        return values;
    }

    /**
     * Intercepts form submission to validate every field before POSTing to PHP.
     */
    $form.on('submit', function (event) {
        const values = collectFormValues();
        const validation = window.StudentValidator.validateAllFields(values);

        if (!validation.isValid) {
            event.preventDefault();

            let firstInvalidField = null;
            let invalidCount = 0;

            $.each(fieldNames, function (_, fieldName) {
                const result = validation.results[fieldName];
                applyFieldUI(fieldName, result);
                if (!result.valid) {
                    invalidCount += 1;
                    if (!firstInvalidField) {
                        firstInvalidField = $('#' + fieldName);
                    }
                }
            });

            $clientErrorSummary.text(
                'Found ' + invalidCount + ' invalid ' + (invalidCount === 1 ? 'field' : 'fields') +
                '. Please correct the highlighted details below.'
            );
            $clientErrorBanner.removeClass('hidden');

            if (firstInvalidField) {
                $('html, body').animate(
                    { scrollTop: firstInvalidField.offset().top - 110 },
                    300
                );
                firstInvalidField.trigger('focus');
            }
            return false;
        }

        // Normalize USN to uppercase before actual PHP POST submission
        $('#usn').val($.trim($('#usn').val()).toUpperCase());

        // Prevent accidental double submissions while PHP processes the form
        $submitBtn.prop('disabled', true).text('Submitting...');
        return true;
    });

    /**
     * Completely resets the form, validation states, and error/success banners.
     */
    $resetBtn.on('click', function (event) {
        event.preventDefault();

        // Explicitly clear all fields (including PHP-preserved old_input values)
        $.each(fieldNames, function (_, fieldName) {
            const $field = $('#' + fieldName);
            $field.val('');
            $field.removeClass('is-valid is-invalid');

            const $feedback = $('#' + fieldName + '_feedback');
            $feedback.removeClass('error-message success-message visible').text('');
        });

        $clientErrorBanner.addClass('hidden');
        if ($serverErrorBanner.length) {
            $serverErrorBanner.addClass('hidden');
        }

        $submitBtn.prop('disabled', false).text('Submit Registration');
        updateAddressCounter();
        $('#full_name').trigger('focus');
    });

    // Initialize address character counter on page load (e.g., after server redirect)
    updateAddressCounter();
});
