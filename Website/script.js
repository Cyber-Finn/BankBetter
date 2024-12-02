$(document).ready(function() {
    const signupForm = $('#signupForm');
    const accountForm = $('#accountForm');
    const loginForm = $('#loginForm');
    const paymentForm = $('#paymentForm');
    const balancesForm = $('#balancesForm');
    const statementForm = $('#statementForm');

    function ajaxRequest(url, method, data, callback) {
        console.log("JSON data: " + JSON.stringify(data));
        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: callback,
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    if (signupForm.length) {
        signupForm.on('submit', function(e) {
            e.preventDefault();
            const userName = $('#userName').val();
            const password = $('#password').val();

            ajaxRequest('../API/index.php?action=createUser', 'POST', {
                user_name: userName,
                password: password
            }, function(data) {
                console.log("SignUp Response:", data);
                if (data && data.message) {
                    alert(data.message);
                    if (data.user_id) {
                        sessionStorage.setItem('user_id', data.user_id);
                    } else {
                        console.error('user_id is missing in the response');
                    }
                } else {
                    alert('Unexpected response format');
                }
            });
        });
    }

    if (accountForm.length) {
        accountForm.on('submit', function(e) {
            e.preventDefault();
            const accountType = $('#accountType').val();
            const userId = sessionStorage.getItem('user_id');

            if (userId) {
                ajaxRequest('../API/index.php?action=createAccount', 'POST', {
                    user_id: userId,
                    account_type_id: accountType
                }, function(data) {
                    console.log("Account Creation Response:", data);
                    if (data && data.message) {
                        alert(data.message);
                    } else {
                        alert('Unexpected response format');
                    }
                });
            } else {
                alert('Error: User ID not found in session storage');
            }
        });
    }

    if (loginForm.length) {
        loginForm.on('submit', function(e) {
            e.preventDefault();
            const userName = $('#loginUserName').val();
            const password = $('#loginPassword').val();

            ajaxRequest('../API/index.php?action=loginUser', 'POST', {
                user_name: userName,
                password: password
            }, function(data) {
                console.log("Login Response:", data);
                if (data && data.message) {
                    alert(data.message);
                    if (data.message === 'Login successful') {
                        sessionStorage.setItem('user_id', data.user_id);
                    }
                } else {
                    alert('Unexpected response format');
                }
            });
        });
    }

    if (paymentForm.length) {
        paymentForm.on('submit', function(e) {
            e.preventDefault();
            const fromAccountId = $('#fromAccountId').val();
            const toAccountId = $('#toAccountId').val();
            const amount = $('#amount').val();
            const description = $('#description').val();

            ajaxRequest('../API/index.php?action=makePayment', 'POST', {
                from_account_id: fromAccountId,
                to_account_id: toAccountId,
                amount: amount,
                description: description
            }, function(data) {
                console.log("Payment Response:", data);
                if (data && data.message) {
                    alert(data.message);
                } else {
                    alert('Unexpected response format');
                }
            });
        });
    }

    if (balancesForm.length) {
        balancesForm.on('submit', function(e) {
            e.preventDefault();
            const userId = sessionStorage.getItem('user_id');

            if (userId) {
                ajaxRequest(`../API/index.php?action=getUserBalances&user_id=${encodeURIComponent(userId)}`, 'GET', null, function(data) {
                    console.log("GetUserBalances Response:", data);
                    if (Array.isArray(data)) {
                        let result = '<h3>Balances:</h3>';
                        data.forEach(account => {
                            result += `<p>Account ID: ${account.account_id}, Balance: ${account.balance}</p>`;
                        });
                        $('#balancesResult').html(result);
                    } else {
                        alert('Unexpected response format');
                    }
                });
            } else {
                alert('Error: User ID not found in session storage');
            }
        });
    }

    if (statementForm.length) {
        statementForm.on('submit', function(e) {
            e.preventDefault();
            const userId = sessionStorage.getItem('user_id');
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();

            if (userId) {
                ajaxRequest(`../API/index.php?action=getBankStatement&user_id=${encodeURIComponent(userId)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, 'GET', null, function(data) {
                    console.log("GetBankStatement Response:", data);
                    if (typeof data === 'object') {
                        let result = '<h3>Bank Statement:</h3>';
                        Object.keys(data).forEach(accountId => {
                            const account = data[accountId];
                            result += `<h4>Account ID: ${accountId}, Type: ${account.account_type}</h4>`;
                            result += '<h5>Debits:</h5>';
                            account.debits.forEach(transaction => {
                                result += `<p>${transaction.timestamp}: -${transaction.amount} (${transaction.description})</p>`;
                            });
                            result += '<h5>Credits:</h5>';
                            account.credits.forEach(transaction => {
                                result += `<p>${transaction.timestamp}: +${transaction.amount} (${transaction.description})</p>`;
                            });
                        });
                        $('#statementResult').html(result);
                    } else {
                        alert('Unexpected response format');
                    }
                });
            } else {
                alert('Error: User ID not found in session storage');
            }
        });
    }
});
