<?php
/**
 * This file is part of the Essence library.
 *
 * @author     Quetzy Garcia <quetzyg@impensavel.com>
 * @copyright  2014-2016
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace Impensavel\Essence;

use RuntimeException;

class EssenceException extends RuntimeException
{
    /**
     * Get the previous Exception message
     *
     * @access  public
     * @return  string|null
     */
    public function getPreviousMessage()
    {
        $previous = $this->getPrevious();

        return $previous ? $previous->getMessage() : null;
    }
}
