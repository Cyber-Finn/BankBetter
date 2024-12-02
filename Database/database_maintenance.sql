-- Since the DB can have a lot of tables when you have lots of users
-- and since you need to remove all dependencies before dropping a table
-- I wrote this procedure to clear all user tables, so that you can delete the Accounts table
DELIMITER //
CREATE PROCEDURE DropUserTables()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE tableName VARCHAR(255);
    DECLARE cur CURSOR FOR 
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND (table_name LIKE 'user_%_debits' OR table_name LIKE 'user_%_credits');
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO tableName;
        IF done THEN
            LEAVE read_loop;
        END IF;
        SET @dropStmt = CONCAT('DROP TABLE ', tableName);
        PREPARE stmt FROM @dropStmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
END //
DELIMITER ;

--calling the proc
CALL DropUserTables();
--then run
DROP TABLE IF EXISTS Accounts;