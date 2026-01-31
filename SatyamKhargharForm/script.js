function validateForm() {
    const phone = document.querySelector('input[name="phone"]').value;
    if (!/^\d{10}$/.test(phone)) {
        alert("Enter valid 10-digit phone number");
        return false;
    }
    return true;
}
