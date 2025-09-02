// Minimal toast helpers used across components.
// If SweetAlert2 is available as `window.Swal` use it, otherwise fallback to console.

export const toastSuccess = (msg) => {
	if (typeof window !== 'undefined' && window.Swal && window.Swal.fire) {
		window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 3000 })
		return
	}
	console.log('SUCCESS:', msg)
}

export const toastError = (msg) => {
	if (typeof window !== 'undefined' && window.Swal && window.Swal.fire) {
		window.Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: msg, showConfirmButton: false, timer: 4000 })
		return
	}
	console.error('ERROR:', msg)
}

export default { toastSuccess, toastError }
