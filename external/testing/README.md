# Lab

Lab is a stupid test "framework."

## API

### needs()

The `needs()` method allows you to include a file.  It is similar to PHP's built in require except that it is a function, not a language construct, and it handles the failure of file inclusion or syntax errors more gracefully.

#### Example(s):

```php
needs(__DIR__ '/path/to/file.php');
```