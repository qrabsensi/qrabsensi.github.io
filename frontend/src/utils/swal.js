import Swal from 'sweetalert2';

// Success alert
export const showSuccess = (title, text = '') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'success',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK'
  });
};

// Error alert
export const showError = (title, text = '') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'error',
    confirmButtonColor: '#d33',
    confirmButtonText: 'OK'
  });
};

// Warning alert
export const showWarning = (title, text = '') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'warning',
    confirmButtonColor: '#f39c12',
    confirmButtonText: 'OK'
  });
};

// Info alert
export const showInfo = (title, text = '') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'info',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK'
  });
};

// Confirmation dialog
export const showConfirm = (title, text = '', confirmText = 'Yes', cancelText = 'No') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: confirmText,
    cancelButtonText: cancelText
  });
};

// Loading alert
export const showLoading = (title = 'Loading...') => {
  return Swal.fire({
    title: title,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
};

// Close loading
export const closeLoading = () => {
  Swal.close();
};

// Toast notification
export const showToast = (title, icon = 'success') => {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer);
      toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
  });

  return Toast.fire({
    icon: icon,
    title: title
  });
};

// Success toast (alias for showToast with success)
export const showSuccessToast = (title) => {
  return showToast(title, 'success');
};

// Error toast (alias for showToast with error)
export const showErrorToast = (title) => {
  return showToast(title, 'error');
};

// Confirm dialog (alias for showConfirm)
export const showConfirmDialog = (title, text = '', confirmText = 'Yes', cancelText = 'No') => {
  return showConfirm(title, text, confirmText, cancelText);
};

export default {
  showSuccess,
  showError,
  showWarning,
  showInfo,
  showConfirm,
  showLoading,
  closeLoading,
  showToast,
  showSuccessToast,
  showErrorToast,
  showConfirmDialog
};
