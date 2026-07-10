<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Huihu\Anonymous\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Huihu\Anonymous\Biscuit;

class BiscuitSerializer extends AbstractSerializer
{
    protected $type = 'biscuits';

    protected function getDefaultAttributes($biscuit)
    {
        if (!($biscuit instanceof Biscuit)) {
            throw new \InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Biscuit::class
            );
        }

        $attrs = [
            'biscuitString' => $biscuit->biscuit_string,
            'isDefault'     => (bool) $biscuit->is_default,
            'isFrozen'      => (bool) $biscuit->is_frozen,
            'canDelete'     => $this->actor->can('delete', $biscuit),
            'canEdit'       => $this->actor->can('edit', $biscuit),
            'createdAt'     => $this->formatDate($biscuit->created_at),
        ];

        if ($this->actor->id === $biscuit->user_id || $this->actor->isAdmin()) {
            $attrs['note'] = $biscuit->note;
        }

        return $attrs;
    }
}
