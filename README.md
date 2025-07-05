# ResizeMode
ResizeMode is a lightweight PHP library that enables you to resize images (PNG, JPEG/JPEG, WEBP, GIF) to whatever sizes demanded by various components of your web application.

## How to Use
To resize an image, you only need to include the class file and create an object instance from the class passing along the following arguments: the image url, the destination to save the image, the targeted width, targeted height, the directory (optional) in a case where the image is not located in your current directory.

NOTE:
Images must be on your local server.

### Example 1
```php
$image = new ResizeMode("images/girl.png", "images/sizes/girl4040.png", 40, 40);
if ($image->status === 1 && $image->msg === "success"):
echo "successfully resized";
endif;
```
This will resize the image (images/girl.png) into a 40, 40 dimension and store in images/sizes/girl4040.png.

Now, take note of the status and msg properties which you can use to monitor the process. If the status returns 0, it means the operation failed and the msg property can provide the reason for such failure.

### Example 2
```php
$image = new ResizeMode("images/girl.png", "images/sizes/girl4040.png", 40, 40, dirname(__FILE__)."/assets");
if ($image->status === 1 && $image->msg === "success"):
echo "successfully resized";
endif;
```
Unlike the first example, here, the directory argument tells the directory to start searching for the image.
```
