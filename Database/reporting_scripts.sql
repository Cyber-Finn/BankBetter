--SQL to find which user owns which accounts
select * from Users where  (select user_id from Accounts where account_id = '');
--SQL to find which accounts belongs to which user
Select * from Accounts where user_id = '';