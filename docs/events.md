# Events

## `PostUpdateCheckEvent`

[:octicons-link-external-16: Reference]({{ repository.blob }}/src/Event/PostUpdateCheckEvent.php){: target=_blank }

This event is being dispatched once the update check process is complete. It provides
the complete `UpdateCheckResult` object and allows further handling of outdated packages.

### Example

```php linenums="1"
<?php

declare(strict_types=1);

namespace My\Vendor;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    // ...
    
    public static function getSubscribedEvents(): array
    {
        return [
            PostUpdateCheckEvent::NAME => [
                ['onPostUpdateCheck']
            ],
        ];
    }
    
    public function onPostUpdateCheck(PostUpdateCheckEvent $event): void
    {
        $updateCheckResult = $event->getUpdateCheckResult();
        
        // ...
    }
}
```
