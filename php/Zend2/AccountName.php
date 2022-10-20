<?php
namespace SP\Validator;

class AccountName extends \Zend\Validator\AbstractValidator
{
    const ACCOUNT_NAME = 'accountName'; /* office\m-b.fedarenko, m-b.fedarenko, office\b.fedarenko, b.fedarenko */

    protected $messageTemplates = [
        self::ACCOUNT_NAME => "Account name must contains only latin chars or digits or symbols: '.', '_', '-', '\\'",
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        $result = \preg_replace('/^((?:[a-z]+\\{1})*(?:(?:[a-z]\-)*(?:[a-z]+[a-z0-1]*[\.\_])*[a-z]+[a-z0-1]*))$/iu', '', $value);

        if (!empty($result)) {
            $this->error(self::ACCOUNT_NAME);
            return false;
        }
        return true;
    }
}