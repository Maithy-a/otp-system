// Toast configuration
const toastConfig = {
    timeout: 4500,
    resetOnHover: true,
    position: "topRight",
    progressBar: true,
    close: true,
    transitionIn: "bounceInDown",
    closeOnEscape: true,
    closeOnClick: true,
    displayMode: "replace",
    layout: 1,
    balloon: false,
    theme: "light"
};

iziToast.settings(toastConfig);

// Helper functions
const showLoadingToast = (title, message) => {
    return iziToast.show({
        title,
        message,
        icon: "fas fa-spinner fa-spin",
        color: 'yellow',
        position: "topRight",
        progressBar: true
    });
};

const handleApiError = (message = "An error occurred. Please try again.") => {
    iziToast.error({
        title: "Error",
        message,
        position: "topRight"
    });
};

// Validation functions
const validatePhoneNumber = (phone) => /^\+254\d{9}$/.test(phone);
const validateOTP = (otp) => /^\d{4}$/.test(otp);

// Phone input handling
const phoneInput = document.getElementById("phone");
if (phoneInput) {
    phoneInput.addEventListener("input", function(e) {
        let value = e.target.value;
        if (!value.startsWith("+254")) {
            value = "+254" + value.replace(/^\+254/, "");
        }
        const prefix = value.substring(0, 4);
        const numbers = value.substring(4).replace(/\D/g, "");
        value = prefix + numbers;
        if (numbers.length > 9) {
            value = prefix + numbers.substring(0, 9);
        }
        e.target.value = value;
        const isValid = validatePhoneNumber(value);
        phoneInput.classList.toggle("is-valid", isValid);
        phoneInput.classList.toggle("is-invalid", !isValid && value.length > 4);
    });

    phoneInput.addEventListener("paste", function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData("text").replace(/\D/g, "");
        let formattedNumber = "+254";
        if (pastedData.startsWith("254")) {
            formattedNumber += pastedData.substring(3, 12);
        } else if (pastedData.startsWith("0")) {
            formattedNumber += pastedData.substring(1, 10);
        } else {
            formattedNumber += pastedData.substring(0, 9);
        }
        phoneInput.value = formattedNumber;
        const isValid = validatePhoneNumber(formattedNumber);
        phoneInput.classList.toggle("is-valid", isValid);
        phoneInput.classList.toggle("is-invalid", !isValid);
    });
}

// Form submission
const otpForm = document.getElementById("otp-form");
if (otpForm) {
    otpForm.addEventListener("submit", async function(e) {
        e.preventDefault();
        const phone = document.getElementById("phone")?.value;
        const otp = document.getElementById("otp")?.value;
        const csrfToken = document.querySelector("input[name=csrf_token]")?.value;

        if (!csrfToken) {
            handleApiError("Security error. Please refresh the page.");
            console.error("CSRF token missing");
            return;
        }

        if (phone) {
            if (!validatePhoneNumber(phone)) {
                handleApiError("Please enter a valid Kenyan phone number (+254XXXXXXXXX)");
                return;
            }
            const loadingToast = showLoadingToast("Sending OTP", "Please wait...");
            try {
                const response = await fetch("send_otp.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `phone=${encodeURIComponent(phone)}&csrf_token=${encodeURIComponent(csrfToken)}`
                });
                const data = await response.json();
                iziToast.destroy();
                if (data.success) {
                    iziToast.success({
                        title: "OTP Sent",
                        message: "OTP sent to your phone.",
                        position: "topRight"
                    });
                    setTimeout(() => {
                        window.location.href = data.redirect || "verify_otp.php";
                        console.log("Redirecting to verify_otp.php");
                    }, 1000);
                } else {
                    handleApiError(data.message || "Failed to send OTP");
                    console.error("OTP send failed:", data.message);
                }
            } catch (error) {
                iziToast.destroy();
                handleApiError(error.message.includes("Failed to fetch") ? "Network error. Please check your connection." : "An unexpected error occurred.");
                console.error("Fetch error:", error);
            }
        } else if (otp) {
            if (!validateOTP(otp)) {
                handleApiError("Please enter a valid 4-digit verification code");
                return;
            }
            const loadingToast = showLoadingToast("Verifying OTP", "Please wait...");
            try {
                const response = await fetch("verify_otp.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `otp=${encodeURIComponent(otp)}&csrf_token=${encodeURIComponent(csrfToken)}`
                });
                const data = await response.json();
                iziToast.destroy();
                if (data.success) {
                    iziToast.success({
                        title: "Verified",
                        message: "Success! Phone number verified.",
                        position: "topRight"
                    });
                    setTimeout(() => {
                        window.location.href = "welcome.php";
                        console.log("Redirecting to welcome.php");
                    }, 2000);
                } else {
                    if (data.expired) {
                        handleApiError(data.message);
                        // Enable resend button
                        const resendButton = document.getElementById("resend-otp");
                        if (resendButton) {
                            resendButton.disabled = false;
                            resendButton.style.display = "inline-block";
                        }
                    } else if (data.maxAttemptsReached) {
                        handleApiError(data.message);
                        // Disable the form and show a message
                        const otpForm = document.getElementById("otp-form");
                        const otpInput = document.getElementById("otp");
                        const verifyButton = otpForm.querySelector("button[type='submit']");
                        otpInput.disabled = true;
                        verifyButton.disabled = true;
                        verifyButton.textContent = "Too Many Attempts";
                        // Show resend button
                        const resendButton = document.getElementById("resend-otp");
                        if (resendButton) {
                            resendButton.style.display = "inline-block";
                        }
                    } else {
                        handleApiError(data.message || "Invalid code");
                        document.getElementById("otp").value = "";
                        document.getElementById("otp").focus();
                    }
                    console.error("OTP verification failed:", data.message);
                }
            } catch (error) {
                iziToast.destroy();
                handleApiError(error.message.includes("Failed to fetch") ? "Network error. Please check your connection." : "An unexpected error occurred.");
                console.error("Fetch error:", error);
            }
        }
    });
}

// Resend OTP functionality
const resendButton = document.getElementById("resend-otp");
if (resendButton) {
    let canResend = true;
    let resendTimer = null;

    // Check for persisted cooldown
    const cooldownEnd = localStorage.getItem("resendCooldown");
    if (cooldownEnd && Date.now() < cooldownEnd) {
        canResend = false;
        resendButton.disabled = true;
        const timeLeft = Math.ceil((cooldownEnd - Date.now()) / 1000);
        startResendCooldown(timeLeft);
    }

    resendButton.addEventListener("click", async function(e) {
        e.preventDefault();
        if (!canResend) return;

        const phoneElement = document.querySelector(".phone-number");
        const phone = phoneElement?.textContent;
        const csrfToken = document.querySelector("input[name=csrf_token]")?.value;

        if (!phone) {
            iziToast.destroy();
            handleApiError("Unable to find your phone number. Please try again.");
            console.error("Phone number not found for resend");
            return;
        }

        if (!csrfToken) {
            iziToast.destroy();
            handleApiError("Security error. Please refresh the page.");
            console.error("CSRF token missing for resend");
            return;
        }

        const loadingToast = showLoadingToast("Resending OTP", "Resending code...");
        try {
            const response = await fetch("send_otp.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `phone=${encodeURIComponent(phone)}&csrf_token=${encodeURIComponent(csrfToken)}`
            });
            const data = await response.json();
            iziToast.destroy();
            if (data.success) {
                iziToast.success({
                    title: "OTP Resent",
                    message: "New code sent to your phone.",
                    position: "topRight"
                });
                startResendCooldown();
                console.log("OTP resent successfully to", phone);
            } else {
                handleApiError(data.message || "Failed to resend OTP");
                console.error("Resend failed:", data.message);
            }
        } catch (error) {
            iziToast.destroy();
            handleApiError(error.message.includes("Failed to fetch") ? "Network error. Please try again." : "An unexpected error occurred.");
            console.error("Resend fetch error:", error);
        }
    });

    function startResendCooldown(timeLeft = 60) {
        canResend = false;
        resendButton.disabled = true;
        localStorage.setItem("resendCooldown", Date.now() + timeLeft * 1000);

        resendTimer = setInterval(() => {
            resendButton.textContent = `Resend Code (${--timeLeft}s)`;
            if (timeLeft <= 0) {
                clearInterval(resendTimer);
                canResend = true;
                resendButton.textContent = "Resend Code";
                resendButton.disabled = false;
                localStorage.removeItem("resendCooldown");
            }
        }, 1000);
    }
}