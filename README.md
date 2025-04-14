# Africas-talking-otp-system

A simple and efficient One-Time Password (OTP) system built using Africa's Talking API. This provides a streamlined solution for generating, sending, and verifying OTPs, making it ideal for secure user authentication in applications.

## Setting Up

### 1. Create an Africa's Talking Account

- Sign up at [Africa's Talking](https://africastalking.com/) if you don't already have an account.
- Use the **Sandbox Environment** for testing:
  - Click on the **Go to Sandbox** button on your dashboard.
  - Navigate to the **Settings** section on the sidebar and select **API Key** to generate your API key.
  - Once you've acquired your API key, return to the dashboard and click **Launch Simulator** to test your integration.

### 2. Create a `.env` File

Create a `.env` file in the root of your project directory and add the following environment variables:

```bash
AFRICASTALKING_USERNAME=sandbox
AFRICASTALKING_API_KEY=YOUR_API_KEY
```

Replace `YOUR_API_KEY` with the API key obtained from Africa's Talking.

### 3. Install Composer Packages

Run the following command to install the required Composer packages:

```bash
composer install
```

### 4. Install Packages Directly (Optional)

Alternatively, you can install the required packages individually:

```bash
composer require africastalking/africastalking
composer require vlucas/phpdotenv
```
