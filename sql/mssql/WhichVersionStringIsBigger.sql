/* it's not mine */
ALTER FUNCTION [dbo].[StrToTableInt](@str VARCHAR(MAX), @separator CHAR(1)=',')
    RETURNS @val TABLE (id INT)  AS  
BEGIN 
    DECLARE @i INT;
    WHILE LEN(RTRIM(@str)) > 0 
    BEGIN
        IF CHARINDEX(@separator, RTRIM(@str)) <> 0
        BEGIN
            INSERT INTO @val
                SELECT SUBSTRING(RTRIM(@str), 1, CHARINDEX(@separator, RTRIM(@str)) - 1);
        
            SET @str = SUBSTRING(RTRIM(@str), CHARINDEX(@separator, RTRIM(@str)) + 1, LEN(RTRIM(@str)));
        END
        ELSE
        BEGIN
            INSERT INTO @val
                SELECT @str;
            
            SET @str = '';
        END
    END

    RETURN;
END

/* it's mine */
CREATE FUNCTION dbo.WhichVersionStringIsBigger(@versionStr1 VARCHAR(100), @versionStr2 VARCHAR(100), @compareCommonPartsOnly bit)
    RETURNS INT AS
BEGIN
    DECLARE @res INT = 0;

    SELECT @res = CASE WHEN val1 > val2 THEN 1 ELSE 2 END
    FROM
    (
        SELECT top 1 COALESCE(t1.ordinal,t2.ordinal) ordinal, ISNULL(t1.id,0) val1, ISNULL(t2.id,0) val2
        FROM (
            SELECT ROW_NUMBER() OVER(ORDER BY (SELECT NULL)) AS ordinal, id FROM [dbo].StrToTableInt(@versionStr1,'.')
        ) t1
        FULL JOIN (
            SELECT ROW_NUMBER() OVER(ORDER BY (SELECT NULL)) AS ordinal, id FROM [dbo].StrToTableInt(@versionStr2,'.')
        ) t2
            ON t2.ordinal = t1.ordinal
        WHERE (@compareCommonPartsOnly = 1 
            AND t2.id <>t1.id)
            OR (@compareCommonPartsOnly = 0 AND ISNULL(t2.id,0) <> ISNULL(t1.id,0))
        ORDER BY 1
    ) PS;

    RETURN @res;
END