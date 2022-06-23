# VToaster

Normal-looking toasts, with full customization (such as icons, background and sounds) 

Resource pack is generated in bootstrap time.

At the moment, this is proof of concept, so I don't give a shit about BC 

# How to create my own toast and use it?

```yaml
# plugin.yml
depend:
  - VToaster
```

```php
use vezdehod\toaster\ToastFactory;
use vezdehod\toaster\ToastOptions;
use pocketmine\level\sound\ClickSound;


private $achievementsToast;

public function onLoad() {
    $this->achievementsToast = ToastFactory::create(ToastOptions::create($this, 'achievements')
            ->downloadIcon("https://cdn-icons-png.flaticon.com/128/3135/3135728.png")
            ->soundOf(ClickSound::class));
}
// And show it to player
$this->achievementsToast->enqueue($player, "§eAchievement unlocked: Spawned", "§6This is your first visit!");

```
