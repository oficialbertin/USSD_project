# Quick Cash USSD Application

Quick Cash is a USSD-based mobile money application that allows users to perform various financial transactions such as sending money, withdrawing money, checking balances, and depositing money. The application also supports agent registration and provides SMS notifications for all transactions.

## Features

- **User Registration**: Users can register with their phone number, name, and PIN.
- **Agent Registration**: Agents can register to facilitate transactions.
- **Send Money**: Users can send money to other registered users.
- **Withdraw Money**: Users can withdraw money via registered agents.
- **Check Balance**: Users can check their account balance.
- **Deposit Money**: Users can deposit money via agents.
- **SMS Notifications**: Users receive SMS notifications for successful transactions.
- **Navigation**: Supports "Go Back" (`98`) and "Go to Main Menu" (`99`) options for easy navigation.

## Technologies Used

- **PHP**: Backend logic for handling USSD requests.
- **MySQL**: Database for storing user and transaction data.
- **Africa's Talking API**: For sending SMS notifications.
- **USSD Gateway**: To handle USSD requests and responses.

## Project Structure


### Prerequisites

1. **Web Server**: Install [XAMPP](https://www.apachefriends.org/) or any other PHP-supported web server.
2. **Database**: Install MySQL or any compatible database server.
3. **Africa's Talking Account**: Create an account at [Africa's Talking](https://africastalking.com/) to get your API key.

### Steps

1. Clone the repository or copy the project files to your web server's root directory (e.g., `c:/xampp/htdocs/USSD_work`).
2. Import the database:
   - Create a database named `quick cash`.
   - Import the provided SQL file (if available) or create the necessary tables:
     - `users`: Stores user information.
     - `agents`: Stores agent information.
     - `transactions`: Stores transaction history.
3. Update the configuration in `util.php`:
   - Set the database credentials (`$host`, `$db`, `$user`, `$pass`).
   - Set your Africa's Talking API credentials (`$username`, `$apikey`).
4. Start the web server and MySQL server.
5. Test the application by sending USSD requests via a USSD gateway or simulator.

---

## Usage

1. Dial the USSD code (*384*7677#) to access the application.
2. Follow the prompts to:
   - Register as a user or agent.
   - Send money, withdraw money, check balance, or deposit money.
3. Use `98` to go back to the previous menu or `99` to return to the main menu.

---

## Example USSD Flow

### User Registration
1. Dial `*384*7677#`.
2. Select `1` for "Register User".
3. Enter your full name.
4. Enter your desired PIN.
5. Confirm your PIN.
6. Receive a confirmation message and SMS.

### Sending Money
1. Dial `*384*7677#`.
2. Select `1` for "Send Money".
3. Enter the recipient's phone number.
4. Enter the amount.
5. Enter your PIN.
6. Confirm the transaction.
7. Receive a confirmation message and SMS.

## SMS Integration

The application uses Africa's Talking API to send SMS notifications. 

## Contact

For any inquiries or support, please contact:

- **Email**: oficialbertin@gmail.com / yvetteuwumukiza99@gmail.com
- **Phone**: +250781065112  / +250790237325
