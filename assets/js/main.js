/**
 * Main JavaScript Functions
 */

// Form validation
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return false;
  
  const inputs = form.querySelectorAll('[required]');
  let isValid = true;
  
  inputs.forEach(input => {
    const errorElement = input.nextElementSibling;
    
    if (!input.value.trim()) {
      isValid = false;
      input.classList.add('is-invalid');
      if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.textContent = 'Este campo es obligatorio';
      }
    } else {
      input.classList.remove('is-invalid');
      if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.textContent = '';
      }
    }
  });
  
  return isValid;
}

// Email validation
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

// Show/Hide password
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
  } else {
    input.type = 'password';
  }
}

// Modal functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
  }
}

// Close modal on outside click
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.classList.remove('active');
    document.body.style.overflow = 'auto';
  }
}

// Alert/Toast notification
function showAlert(message, type = 'info', duration = 5000) {
  const alertContainer = document.getElementById('alert-container') || createAlertContainer();
  
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.style.cssText = 'animation: slideInRight 0.3s ease; margin-bottom: 10px;';
  alert.innerHTML = `
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; cursor: pointer; font-size: 1.2rem;">&times;</button>
  `;
  
  alertContainer.appendChild(alert);
  
  if (duration > 0) {
    setTimeout(() => {
      alert.style.animation = 'slideOutRight 0.3s ease';
      setTimeout(() => alert.remove(), 300);
    }, duration);
  }
}

function createAlertContainer() {
  const container = document.createElement('div');
  container.id = 'alert-container';
  container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
  document.body.appendChild(container);
  return container;
}

// Confirm dialog
function confirmAction(message, callback) {
  if (confirm(message)) {
    callback();
  }
}

// AJAX helper
async function fetchData(url, options = {}) {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Fetch error:', error);
    showAlert('Error al conectar con el servidor', 'danger');
    throw error;
  }
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(amount);
}

// Debounce function for search inputs
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Export table to Excel
function exportTableToExcel(tableId, filename = 'export') {
  const table = document.getElementById(tableId);
  if (!table) return;
  
  const html = table.outerHTML;
  const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename + '.xls';
  link.click();
}

// Print function
function printContent(elementId) {
  const content = document.getElementById(elementId);
  if (!content) return;
  
  const printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Imprimir</title>');
  printWindow.document.write('<link rel="stylesheet" href="../assets/css/style.css">');
  printWindow.document.write('</head><body>');
  printWindow.document.write(content.innerHTML);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.print();
}

// Sidebar toggle for dashboard
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) {
    sidebar.classList.toggle('collapsed');
  }
}

// Initialize tooltips (if using any library)
document.addEventListener('DOMContentLoaded', function() {
  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });
  
  // Add animation to cards on scroll
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animation = 'fadeInUp 0.5s ease';
      }
    });
  });
  
  document.querySelectorAll('.card').forEach(card => {
    observer.observe(card);
  });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  
  @keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
  }
  
  @keyframes fadeInUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  
  .is-invalid {
    border-color: #ef4444 !important;
  }
`;
document.head.appendChild(style);
