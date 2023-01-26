<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

/**
 * Upload image and recognize shapes
 */
class Upload
{

    public static function uploadImage(Request $request) : string|null
    {
        try{
            $picture = $request->file('picture');

            $upload_path = config('filesystems.disks.uploads.root');

            if( !file_exists($upload_path) )
                mkdir($upload_path,0775,true);

            $filename = Session::getId().'.'.$picture->getClientOriginalExtension();

            Storage::disk('uploads')->putFileAs('', $picture, $filename );

        } catch( \Exception $e){
            \Log::info($e->getMessage() . "\nfile ". $e->getFile() . "\nline ".$e->getLine());
            $filename = null;
        }
        return $filename;
    }

    /**
     * Recognizes 3 shapes: triangle, square and circle
     * @param string $filename name of uploaded file relatively Storage disk
     * @return string|null
     */
    public static function recognizeImage(string $filename) : string|null
    {
        try{

            $points = self::getBorderPoints($filename);

            $sidesCount = self::getSidesCount($points);

             $figure = match ($sidesCount) {
                -1 => 'Not recognized',
                 3 => 'Triangle',
                4 => 'Square',
                default => 'Circle',
            };

        } catch( \Exception $e){
            \Log::info($e->getMessage() . "\nfile ". $e->getFile() . "\nline ".$e->getLine());
            $figure = null;
        }
        return $figure;
    }

    /**
     * Git points of shape border with steps
     * @param string $filename name of uploaded file relatively Storage disk
     * @return array
     */
    private static function getBorderPoints(string $filename):array
    {
        try {
            $points = [];

            $image = self::getImage($filename);

            if ($image) {

                $width = imagesx($image);

                $height = imagesy($image);

                $stepx = (int)ceil($width / 30);

                $stepy = (int)ceil($height / 30);

                $xx = [];
                $xx[] = 1;
                for ($x = 2; $x < $width; $x += $stepx) {
                    $xx[] = $x;
                }
                $xx[] = $width - 1;
                $xx = array_unique($xx);

                $yy = [];
                $yy[] = 1;
                for ($y = 2; $y < $height; $y += $stepy) {
                    $yy[] = $y;
                }
                $yy[] = $height - 1;
                $yy = array_unique($yy);

                $yy_exists = [];

                foreach ($yy as $y) {
                    $previous = 1;
                    $direction = 1; //white-to-black
                    foreach ($xx as $x) {
                        {

                            $color = imagecolorat($image, $x, $y);

                            if (
                                (($color == 0) && ($previous != 0))
                                ||
                                (($color != 0) && ($previous == 0))
                                ||
                                (($color == 0) && (($x == 1) || ($x == $width - 1)||($y == 1) || ($y == $height - 1)))
                            ) {
                                $points[] = ['x'=>$x,'y'=>$y, 'direction' => $direction];
                                $direction = -1; // black-to-white

                                if( !in_array($y, $yy_exists) )
                                    $yy_exists[] = $y;
                            }

                            $previous = $color;
                        }
                    }
                }
            }
        }catch (\Exception $e){
            \Log::info($e->getMessage() . "\nfile ". $e->getFile() . "\nline ".$e->getLine());
            $points=[];
        }

        $points = self::sorting( $yy_exists, $points);



        return $points;
    }

    /**
     * Get count of sides of shape
     * @param array $points points of border of figure
     * @return int
     */
    private static function getSidesCount(array $points) : int
    {
        if( count($points) < 3 )
            return -1;

        $sides = [];
        $line = [];

        $previous = null;
        $side_dx = null;
        $side_dy = null;

        $points[] = $points[0];

        foreach( $points as $point){
            if( $previous === null ){
                $previous = $point;
                continue;
            }

            $dx = $point['x'] - $previous['x'];
            $dy = $point['y'] - $previous['y'];

            if( $side_dx === null ){
                $side_dx = $dx;
                $side_dy = $dy;
                continue;
            }

            $tg = self::tg($dx,$dy) ;

            $tg_side = self::tg($side_dx,$side_dy) ;
/*
            echo '<br />'. $point['x'] . ', '. $point['y']
            . " \$tg="  . $tg . ' $tg_side=' . $tg_side;
*/
            if(
                abs( $tg - $tg_side ) > 0.3
            ){
                if( count($line) >= 2  )
                    $sides[] = $line;

                $line=[];

                $side_dx = null;
                $side_dy = null;

            } else {
                if( count($line) == 0 )
                    $line[] = $previous;
                $line[] = $point;
            }

            $previous = $point;

        }

        if( count($line) >= 2)
            $sides[] = $line;

        return count($sides);
    }

    private static function getImage(string $filename) : object|null
    {
        $upload_path = config('filesystems.disks.uploads.root');

        $fillname =  $upload_path .'/'.$filename;

        $info = getimagesize($fillname);

        if(!$info){
            \Log::info(__METHOD__.": file is not image");
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_GIF => imagecreatefromgif($fillname),
            IMAGETYPE_JPEG => imagecreatefromjpeg($fillname),
            IMAGETYPE_PNG => imagecreatefrompng($fillname),
            default => null,
        };

        return $image;
    }

    private static function sorting( array $yy_exists, array $points):array
    {

        sort($yy_exists);

        //Строим левую часть, по часовой стрелке снизу вверх

        $left = [];

        for($i=count($yy_exists)-1; $i > 0  ; $i--) {

            for($j=0; $j < count($points)   ; $j++) {

                if( $points[$j]['y'] == $yy_exists[$i] && $points[$j]['direction'] == 1 ){
                    $left[] = $points[$j];
                }

            }

        }

        //Строим правую часть, по часовой стрелке сверху вниз
        $right = [];

        for($i=0; $i < count($yy_exists)  ; $i++) {

            for($j=0; $j < count($points)   ; $j++) {

                if( $points[$j]['y'] == $yy_exists[$i] && $points[$j]['direction'] == -1 ){
                    $right[] = $points[$j];
                }

            }

        }

        $points = array_merge($left, $right);


        return $points;
    }

    private static function tg(int $dx, int $dy)
    {

        if( $dx == 0 )
            $tg = $dy > 0 ? 1 : -1;
        else
            $tg = $dy/$dx;
        return $tg;
    }


}
