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

interface EssenceInterface
{
    /**
     * Register an Element
     *
     * @param   array  $element Element
     * @param   string $key     Element key
     * @throws  EssenceException
     * @return  void
     */
    public function register(array $element, $key = 'default');

    /**
     * Extract data
     *
     * @param   mixed  $input  Input data
     * @param   array  $config Configuration settings (optional)
     * @param   mixed  $data   User data (optional)
     * @throws  EssenceException
     * @return  bool
     */
    public function extract($input, array $config = array(), &$data = null);
}
