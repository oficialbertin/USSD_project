<?php
require_once 'sms.php';
require_once 'db.php';
require_once 'util.php';

class Menu {
    protected $text;
    protected $sessionId;
    protected $phoneNumber;
    protected $conn;

    function __construct($text, $sessionId, $phoneNumber, $conn) {
        $this->text = $text;
        $this->sessionId = $sessionId;
        $this->phoneNumber = $phoneNumber;
        $this->conn = $conn;
    }

    public function mainMenuUnregistered() {
        echo "CON Welcome to Quick Cash\n1. Register User\n2. Register Agent";
    }

    public function mainMenuRegistered() {
        echo "CON Welcome back to Quick Cash\n";
        echo "1. Send Money\n";
        echo "2. Withdraw Money\n";
        echo "3. Check Balance\n";
        echo "4. Deposit Money\n";
        echo "99. Exit";
    }

    public function RegisterAgent($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter your full Agent name";
        } elseif ($level == 2) {
            echo "CON Enter your PIN";
        } elseif ($level == 3) {
            echo "CON Re-enter your PIN";
        } elseif ($level == 4) {
            $name = trim($textArray[1]);
            $pin = trim($textArray[2]);
            $confirmPin = trim($textArray[3]);

            // Check if PINs match
            if ($pin !== $confirmPin) {
                echo "END PINs do not match. Please try again.";
                return;
            }

            // Check if phone is already registered as an agent
            $stmt = $this->conn->prepare("SELECT * FROM agents WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            if ($stmt->rowCount() > 0) {
                echo "END This phone number is already registered as an agent.";
                return;
            }

            // Hash PIN and insert into database
            $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO agents (phone_number, full_name, pin) VALUES (?, ?, ?)");
            if ($stmt->execute([$this->phoneNumber, $name, $hashedPin])) {
                $message = "Dear $name, you have successfully registered as an agent.";
                $sms = new Sms();
                $sms->sendSMS($message, $this->phoneNumber);

                echo "END Dear $name, you have successfully registered as an agent.";
            } else {
                echo "END Agent registration failed. Please try again.";
            }
        } else {
            echo "END Invalid input. Please try again.";
        }
    }

    public function menuRegister($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter your full User name";
        } elseif ($level == 2) {
            echo "CON Enter your PIN";
        } elseif ($level == 3) {
            echo "CON Re-enter your PIN";
        } elseif ($level == 4) {
            $name = trim($textArray[1]);
            $pin = trim($textArray[2]);
            $confirmPin = trim($textArray[3]);

            // Check if PINs match
            if ($pin !== $confirmPin) {
                echo "END PINs do not match. Please try again.";
                return;
            }

            // Check if phone is already registered
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            if ($stmt->rowCount() > 0) {
                echo "END This phone number is already registered.";
                return;
            }

            // Hash PIN and insert into database
            $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (phone_number, full_name, pin, balance) VALUES (?, ?, ?, 500)");
            if ($stmt->execute([$this->phoneNumber, $name, $hashedPin])) {
                $message = "Dear $name, you have successfully registered. Your initial balance is 500 Rwf.";
                $sms = new Sms();
                $sms->sendSMS($message, $this->phoneNumber);

                echo "END Dear $name, you have successfully registered. Initial balance Account is: 500 Rwf";
            } else {
                echo "END Registration failed. Please try again.";
            }
        } else {
            echo "END Invalid input. Please try again.";
        }
    }

    public function menuSendMoney($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter recipient phone number";
        } elseif ($level == 2) {
            echo "CON Enter amount";
        } elseif ($level == 3) {
            echo "CON Enter PIN";
        } elseif ($level == 4) {
            list(, $recipient, $amount, $pin) = $textArray;

            // Confirm transaction
            echo "CON Confirm sending $amount Rwf to $recipient\n1. Confirm\n98. Go Back\n99. Main Menu";
        } elseif ($level == 5) {
            if ($textArray[4] == "1") {
                list(, $recipient, $amount, $pin) = $textArray;

                $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $sender = $stmt->fetch();

                if (!$sender || !password_verify($pin, $sender['pin'])) {
                    echo "END Incorrect PIN.";
                    return;
                }

                $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
                $stmt->execute([$recipient]);
                $receiver = $stmt->fetch();

                if (!$receiver) {
                    echo "END Recipient does not exist.";
                    return;
                }

                if ($sender['balance'] < $amount) {
                    echo "END Insufficient balance.";
                    return;
                }

                $this->conn->beginTransaction();

                $this->conn->prepare("UPDATE users SET balance = balance - ? WHERE phone_number = ?")
                    ->execute([$amount, $this->phoneNumber]);

                $this->conn->prepare("UPDATE users SET balance = balance + ? WHERE phone_number = ?")
                    ->execute([$amount, $recipient]);

                $this->conn->prepare("INSERT INTO transactions (sender_phone, recipient_phone, amount, transaction_type) VALUES (?, ?, ?, 'SEND')")
                    ->execute([$this->phoneNumber, $recipient, $amount]);

                $this->conn->commit();

                $senderMessage = "You have sent $amount Rwf to $receiver[full_name] ($recipient). Your new balance is " . ($sender['balance'] - $amount) . " Rwf.";
                $recipientMessage = "You have received $amount Rwf from $sender[full_name] ($this->phoneNumber). Your new balance is " . ($receiver['balance'] + $amount) . " Rwf.";

                $sms = new Sms();
                $sms->sendSMS($senderMessage, $this->phoneNumber);
                $sms->sendSMS($recipientMessage, $recipient);

                echo "END You have sent $amount Rwf to $receiver[full_name] ($recipient) successfully.";
            } elseif ($textArray[4] == "98") {
                echo "CON Enter recipient phone number";
            } elseif ($textArray[4] == "99") {
                $this->mainMenuRegistered();
            } else {
                echo "END Invalid option. Please try again.";
            }
        }
    }

    public function menuCheckBalance($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter your PIN";
        } elseif ($level == 2) {
            $pin = $textArray[1];

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($pin, $user['pin'])) {
                echo "END Incorrect PIN.";
                return;
            }

            $balance = $user['balance'];
            $message = "Hello, your current balance is: $balance Rwf.";

            $sms = new Sms();
            $sms->sendSMS($message, $this->phoneNumber);

            echo "END Hello, your current balance is: $balance Rwf. Your balance also sent via SMS.";
        }
    }

    public function menuWithdrawMoney($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter amount";
        } elseif ($level == 2) {
            echo "CON Enter agent phone number";
        } elseif ($level == 3) {
            echo "CON Enter your PIN";
        } elseif ($level == 4) {
            list(, $amount, $agentPhone, $pin) = $textArray;

            // Confirm withdrawal
            echo "CON Confirm withdrawal of $amount Rwf via agent $agentPhone\n1. Confirm\n98. Go Back\n99. Main Menu";
        } elseif ($level == 5) {
            if ($textArray[4] == "1") {
                list(, $amount, $agentPhone, $pin) = $textArray;

                $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($pin, $user['pin'])) {
                    echo "END Incorrect PIN.";
                    return;
                }

                if ($user['balance'] < $amount) {
                    echo "END Insufficient balance.";
                    return;
                }

                $stmt = $this->conn->prepare("SELECT * FROM agents WHERE phone_number = ?");
                $stmt->execute([$agentPhone]);
                $agent = $stmt->fetch();

                if (!$agent) {
                    echo "END Agent not found.";
                    return;
                }

                $this->conn->beginTransaction();

                $this->conn->prepare("UPDATE users SET balance = balance - ? WHERE phone_number = ?")
                    ->execute([$amount, $this->phoneNumber]);

                $this->conn->prepare("INSERT INTO transactions (sender_phone, amount, transaction_type, agent_phone) VALUES (?, ?, 'WITHDRAW', ?)")
                    ->execute([$this->phoneNumber, $amount, $agentPhone]);

                $this->conn->commit();

                $message = "You have successfully withdrawn $amount Rwf via agent $agentPhone. Your new balance is " . ($user['balance'] - $amount) . " Rwf.";
                $sms = new Sms();
                $sms->sendSMS($message, $this->phoneNumber);

                echo "END You have successfully withdrawn $amount Rwf via agent $agentPhone.";
            } elseif ($textArray[4] == "98") {
                echo "CON Enter amount";
            } elseif ($textArray[4] == "99") {
                $this->mainMenuRegistered();
            } else {
                echo "END Invalid option. Please try again.";
            }
        }
    }

    public function menuDepositMoney($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter deposit amount";
        } elseif ($level == 2) {
            echo "CON Enter agent phone number";
        } elseif ($level == 3) {
            echo "CON Enter your PIN";
        } elseif ($level == 4) {
            list(, $amount, $agentPhone, $pin) = $textArray;

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($pin, $user['pin'])) {
                echo "END Incorrect PIN.";
                return;
            }

            $stmt = $this->conn->prepare("SELECT * FROM agents WHERE phone_number = ?");
            $stmt->execute([$agentPhone]);
            $agent = $stmt->fetch();

            if (!$agent) {
                echo "END Agent not found.";
                return;
            }

            $this->conn->beginTransaction();

            $this->conn->prepare("UPDATE users SET balance = balance + ? WHERE phone_number = ?")
                ->execute([$amount, $this->phoneNumber]);

            $this->conn->prepare("INSERT INTO transactions (recipient_phone, amount, transaction_type, agent_phone) VALUES (?, ?, 'DEPOSIT', ?)")
                ->execute([$this->phoneNumber, $amount, $agentPhone]);

            $this->conn->commit();

            $message = "You have successfully deposited $amount Rwf via agent $agentPhone. Your new balance is " . ($user['balance'] + $amount) . " Rwf.";
            $sms = new Sms();
            $sms->sendSMS($message, $this->phoneNumber);

            echo "END Deposit of $amount Rwf successful via agent $agentPhone.";
        }
    }

    public function middleware($text) {
        return $this->goBack($this->goBackMenu($text));
    }

    public function goBack($text) {
        $explodedText = explode("*", $text);
        while (array_search('98', $explodedText) != false) {
            $firstIndex = array_search('98', $explodedText);
            array_splice($explodedText, $firstIndex - 1, 2);
        }
        return join("*", $explodedText);
    }

    public function goBackMenu($text) {
        $explodedText = explode("*", $text);
        while (array_search('99', $explodedText) != false) {
            $firstIndex = array_search('99', $explodedText);
            $explodedText = array_slice($explodedText, $firstIndex + 1);
        }
        return join("*", $explodedText);
    }
}
?>