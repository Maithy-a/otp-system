// Toast configuration
const toastConfig = {
    timeout: 3000,
    resetOnHover: true,
    transitionIn: "flipInX",
    transitionOut: "flipOutX",
    position: "topRight",
    progressBar: true,
    close: true,
    closeOnEscape: true,
    closeOnClick: true,
    displayMode: "replace",
    layout: 2,
    balloon: true,
    theme: "light"
};

iziToast.settings(toastConfig);

// Helper functions
const showLoadingToast = (title, message) => {
    return iziToast.show({
        title,
        message,
        icon: "fa fa-spinner fa-spin",
        timeout: false,
        close: false,
        position: "topRight",
        progressBar: true
    });
};

const handleApiError = (errorMessage = "An error occurred. Please try again.") => {
    iziToast.error({
        title: "Error",
        message: errorMessage,
        position: "topRight"
    });
};

// Validation functions
const validatePhoneNumber = (phone) => /^\+254\d{9}$/.test(phone);
const validateOTP = (otp) => otp.length === 4;

// Phone input handling
const phoneInput = document.getElementById("phone");
if (phoneInput) {
    phoneInput.addEventListener("input", function(e) {
        const value = e.target.value;
        const isValid = /^\+254\d{0,9}$/.test(value);
        
        phoneInput.classList.toggle("is-valid", isValid && value.length > 0);
        phoneInput.classList.toggle("is-invalid", !isValid && value.length > 0);
    });

    phoneInput.addEventListener("paste", function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData("text").replace(/\D/g, "");
        
        if (pastedData.length === 12) {
            phoneInput.value = `+${pastedData}`;
        } else if (pastedData.length === 9) {
            phoneInput.value = `+254${pastedData}`;
        } else {
            handleApiError("Invalid phone number format");
        }
    });
}

// Form submission handling
const otpForm = document.getElementById("otp-form");
if (otpForm) {
    otpForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const phone = document.getElementById("phone")?.value;
        const otp = document.getElementById("otp")?.value;

        if (phone && !validatePhoneNumber(phone)) {
            handleApiError("Please enter a valid Kenyan phone number (+254XXXXXXXXX)");
            return;
        }

        if (otp && !validateOTP(otp)) {
            handleApiError("Please enter a complete 4-digit verification code");
            return;
        }

        const endpoint = phone ? "send_otp.php" : "verify_otp.php";
        const body = phone ? `phone=${encodeURIComponent(phone)}` : `otp=${otp}`;
        const loadingTitle = phone ? "Sending OTP" : "Verifying OTP";
        
        const loadingToast = showLoadingToast(loadingTitle, "Please wait...");

        fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body
        })
        .then(response => response.json())
        .then(data => {
            iziToast.destroy();
            if (data.success) {
                iziToast.success({
                    title: phone ? "OTP Sent" : "Verified",
                    message: phone ? "OTP sent to your phone." : "Success! Phone number verified.",
                    position: "topRight"
                });
                
                setTimeout(() => {
                    window.location.href = phone ? "verify_otp.php" : "welcome.php";
                }, phone ? 0 : 2000);
            } else {
                handleApiError(data.message || (phone ? "Failed to send OTP" : "Invalid code"));
                if (!phone) {
                    document.getElementById("otp").value = "";
                    document.getElementById("otp").focus();
                }
            }
        })
        .catch(() => {
            iziToast.destroy();
            handleApiError();
        });
    });
}

// Resend OTP functionality
const resendButton = document.getElementById("resend-otp");
if (resendButton) {
    let canResend = true;
    let resendTimer = null;

    resendButton.addEventListener("click", function(e) {
        e.preventDefault();
        if (!canResend) return;

        const loadingToast = showLoadingToast("Resending OTP", "Resending code...");

        // Get phone from the welcome message or input
        const welcomeText = document.querySelector(".welcome-section h2")?.textContent;
        const phone = welcomeText ? welcomeText.match(/Welcome, (.*?)!/)?.[1] : 
                    document.getElementById("phone")?.value;

        if (!phone) {
            iziToast.destroy();
            handleApiError("Unable to find your phone number. Please try again.");
            return;
        }

        fetch("send_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `phone=${encodeURIComponent(phone)}`
        })
        .then(response => response.json())
        .then(data => {
            iziToast.destroy();
            if (data.success) {
                iziToast.success({
                    title: "OTP Resent",
                    message: "New code sent to your phone.",
                    position: "topRight"
                });
                startResendCooldown();
            } else {
                handleApiError(data.message || "Failed to resend OTP");
            }
        })
        .catch(() => {
            iziToast.destroy();
            handleApiError();
        });
    });

    function startResendCooldown() {
        canResend = false;
        resendButton.disabled = true;
        let timeLeft = 60;

        resendTimer = setInterval(() => {
            resendButton.textContent = `Resend Code (${--timeLeft}s)`;
            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                canResend = true;
                resendButton.textContent = "Resend Code";
                resendButton.disabled = false;
            }
        }, 1000);
    }
}