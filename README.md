# VToaster

Normal-looking toasts, with full customization of icons and sounds (RP is generated in bootstrap time)

At the moment, this is proof of concept, so I don't give a shit about BC 

# How to use it?

```yaml
# plugin.yml
depend:
  - VToaster
```

```php
private Toast $achievementsToast;

protected function onLoad(): void {
    $this->achievementsToast = ToastFactory::create(ToastOptions::create($this, 'achievements')
            ->downloadSound("https://static.wikia.nocookie.net/geometry-dash/images/1/18/AchievementTone.ogg")
            ->defaultIcon("https://cdn-icons-png.flaticon.com/128/3135/3135728.png"));
}
// And show it to player
$this->achievementsToast->enqueue($player, "§eAchievement unlocked: Spawned", "§6This is your first visit!");

```