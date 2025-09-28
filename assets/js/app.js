// CRM System JavaScript Functions

// Global variables
let currentUser = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

// Utility functions
function showAlert(message, type = 'info', permanent = false) {
    const alertClass = permanent ? 'alert-permanent' : '';
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show ${alertClass}" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at the top of the main content
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
}

function showLoading(element) {
    if (element) {
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        element.disabled = true;
    }
}

function hideLoading(element, originalText) {
    if (element) {
        element.innerHTML = originalText;
        element.disabled = false;
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// API functions
async function apiCall(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(`api/${endpoint}`, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        showAlert('An error occurred while processing your request.', 'danger');
        throw error;
    }
}

// Form handling
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

function resetForm(form) {
    form.reset();
    // Clear any validation classes
    const inputs = form.querySelectorAll('.is-invalid, .is-valid');
    inputs.forEach(input => {
        input.classList.remove('is-invalid', 'is-valid');
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    return isValid;
}

// Modal functions
function showModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) {
        modal.hide();
    }
}

// Table functions
function refreshTable(tableId) {
    const table = $(`#${tableId}`).DataTable();
    table.ajax.reload();
}

function deleteRecord(endpoint, id, tableId = null) {
    confirmAction('Are you sure you want to delete this record?', async () => {
        try {
            await apiCall(`${endpoint}/${id}`, 'DELETE');
            showAlert('Record deleted successfully.', 'success');
            
            if (tableId) {
                refreshTable(tableId);
            } else {
                location.reload();
            }
        } catch (error) {
            showAlert('Failed to delete record.', 'danger');
        }
    });
}

// Date formatting
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Status badges
function getStatusBadge(status) {
    const statusClasses = {
        'Pending': 'status-pending',
        'In Progress': 'status-in-progress',
        'Done': 'status-done',
        'Draft': 'status-pending',
        'Sent': 'status-in-progress',
        'Accepted': 'status-done',
        'Rejected': 'bg-danger',
        'High': 'priority-high',
        'Medium': 'priority-medium',
        'Low': 'priority-low'
    };
    
    const className = statusClasses[status] || 'bg-secondary';
    return `<span class="badge ${className}">${status}</span>`;
}

// Google Maps integration
function openGoogleMaps(address) {
    if (address) {
        const encodedAddress = encodeURIComponent(address);
        window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
    }
}

// File upload handling
function handleFileUpload(input, previewId) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Search and filter functions
function filterTable(tableId, column, value) {
    const table = $(`#${tableId}`).DataTable();
    table.column(column).search(value).draw();
}

function clearFilters(tableId) {
    const table = $(`#${tableId}`).DataTable();
    table.search('').columns().search('').draw();
}

// Export functions
function exportTable(tableId, format = 'csv') {
    const table = $(`#${tableId}`).DataTable();
    
    if (format === 'csv') {
        table.button('.buttons-csv').trigger();
    } else if (format === 'excel') {
        table.button('.buttons-excel').trigger();
    } else if (format === 'pdf') {
        table.button('.buttons-pdf').trigger();
    }
}

// Real-time updates
function startRealTimeUpdates() {
    // Check for updates every 30 seconds
    setInterval(async () => {
        try {
            const response = await apiCall('updates.php');
            if (response.hasUpdates) {
                showAlert('New updates available. Refresh the page to see them.', 'info', true);
            }
        } catch (error) {
            console.error('Failed to check for updates:', error);
        }
    }, 30000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + N for new record
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const newButton = document.querySelector('[data-action="new"]');
        if (newButton) {
            newButton.click();
        }
    }
    
    // Ctrl + S for save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const saveButton = document.querySelector('[data-action="save"]');
        if (saveButton) {
            saveButton.click();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) {
                modal.hide();
            }
        }
    }
});

// Initialize real-time updates (disabled to prevent errors)
// startRealTimeUpdates();
