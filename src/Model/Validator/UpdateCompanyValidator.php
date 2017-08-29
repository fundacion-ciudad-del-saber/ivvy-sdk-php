<?php
declare(strict_types=1);

namespace Fcds\Ivvy\Model\Validator;

use Iterator;
use Fcds\Ivvy\Model\Company;

/**
 * Class: UpdateCompanyValidatorTest
 *
 * Contains the business rules for adding a Company.
 *
 * @see Validator
 */
class UpdateCompanyValidator implements Validator
{
    use ProcessBusinessRuleTrait;

    protected function process(Company $company): Iterator
    {
        if (! $company->id) {
            yield 'An id is needed to update a Company';
        }

        yield;
    }
}
