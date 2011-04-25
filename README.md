## INSTALL

1. Clone this repo into fuel/packages/s3
2. Create config/s3.php - see config/s3.php.sample for details.

## Use

This S3 uploader extends Fuel's Core Upload Class. That means you have all the parent class's
methods available to you. 

http://fuelphp.com/docs/classes/upload/config.html

To save to S3:

	\S3\Upload::save();

## Please fork this repo

I know just enough to be dangerous. This is my first Fuel package and I am sure
it isn't all that pretty. Please fork this repo and let me know where I have 
done something wrong.
