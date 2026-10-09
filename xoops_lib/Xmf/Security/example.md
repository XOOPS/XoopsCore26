## Debug Mode for Serializer

**Enable during migration testing**
```php
Serializer::enableDebug();
```

**Run your operations**
```php
$data1 = Serializer::from($payload1);
$data2 = Serializer::from($payload2);
```

**Get performance stats**
```php
$stats = Serializer::getDebugStats();
// For debugging only; disable debug mode in production to avoid performance impact.
error_log('Serializer stats: ' . json_encode($stats));
```


## SerializableTrait

```php
use Xmf\Security\SerializableTrait;
use Xmf\Security\Format;

class ForumForum extends XoopsObject
{
    use SerializableTrait;

    protected function getSerializableProperties(): array
    {
        return [
            'forum_moderators' => Format::JSON,
            'forum_settings' => Format::JSON,
        ];
    }

    public function getModerators(): array
    {
        $data = $this->getVar('forum_moderators');
        return $this->unserializeProperty($data, [], []); // no object deserialization allowed
    }

    public function setModerators(array $moderators): void
    {
        $data = $this->serializeProperty($moderators);
        $this->setVar('forum_moderators', $data);
    }
}
```
