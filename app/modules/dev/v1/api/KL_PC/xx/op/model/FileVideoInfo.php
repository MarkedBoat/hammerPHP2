<?php

namespace modules\dev\v1\api\KL_PC\xx\op\model;

use models\ext\tool\Printer;

class FileVideoInfo
{

    public Printer        $printer;
    public string         $fullPath;
    public string         $dir;
    public string         $fileName;

    public string $baseName;
    public string $ext;

    public int    $rateK;
    public int    $height;
    public int    $width;
    public int    $fps;
    public int    $durationSeconds;
    public string $durationStr;

    public bool $isPhone;

    public function __construct(string $fullPath)
    {
        $this->reloadFileinfo($fullPath);
    }

    private function reloadFileinfo(string $fullPath)
    {
        $this->fullPath = $fullPath;
        $file_info      = pathinfo($fullPath);
        $this->dir      = $file_info['dirname'];
        $this->baseName = $file_info['filename'];
        $this->ext      = $file_info['extension'];
        return $this;
    }


    /**
     * @param Printer $printer
     * @return static
     */
    public function setPrinter(Printer $printer)
    {
        $this->printer = $printer;
        return $this;
    }



    public function initVideoInfo(&$msgs = [])
    {
        $tmp     = [];
        $tmp_cmd = "ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width,nb_frames,avg_frame_rate,r_frame_rate  -print_format json '{$this->fullPath}'";
        $this->printer->tabEcho("info common CMD: \n$tmp_cmd\n");
        exec($tmp_cmd, $tmp);
        $video_info = json_decode(join('', $tmp), true);
        $this->printer->tabEcho(json_encode($video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->width           = intval($video_info['streams'][0]['width'] ?? 0);
        $this->height          = intval($video_info['streams'][0]['height'] ?? 0);
        $video_rate            = intval($video_info['format']['bit_rate'] ?? 0);
        $this->durationSeconds = 0;
        // var_dump($video_info);
        $this->fps = 0;
        if (isset($video_info['format']['duration']))
        {
            $this->durationSeconds = intval($video_info['format']['duration']);
        }
        if (isset($video_info['streams'][0]['nb_frames']))
        {

            if ($this->durationSeconds < 2)
            {
                $msgs[] = 'duration length error:' . $this->durationSeconds;
                return false;
            }
            else
            {
                $this->fps = intval($video_info['streams'][0]['nb_frames'] / $this->durationSeconds);
            }
            // var_dump($fps);
        }
        else if (isset($video_info['streams'][0]['avg_frame_rate']) && str_ends_with($video_info['streams'][0]['avg_frame_rate'], '/1'))
        {
            $this->fps = intval(str_replace('-1', '', $video_info['streams'][0]['avg_frame_rate']));
        }
        else
        {
            $tmp_cmd = "ffprobe -hide_banner -v error \
  -v panic\
  -select_streams v:0 \
  -show_entries stream=codec_name,avg_frame_rate,r_frame_rate,width,height,nb_read_frames \
  -show_entries format=duration,bit_rate,size \
  -print_format json '{$this->fullPath}'";
            $this->printer->tabEcho("info special CMD: \n$tmp_cmd\n");
            $tmp = [];
            exec($tmp_cmd, $tmp);
            $video_info = json_decode(join('', $tmp), true);
            $this->printer->tabEcho(json_encode($video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if (isset($video_info['format']['duration']))
            {
                $this->durationSeconds = intval($video_info['format']['duration']);
            }
            if (isset($video_info['streams'][0]['nb_read_frames']))
            {
                $this->fps = intval($video_info['streams'][0]['nb_read_frames'] / $video_info['format']['duration']);
            }
            elseif (isset($video_info['streams'][0]['avg_frame_rate']) && str_ends_with($video_info['streams'][0]['avg_frame_rate'], '/1'))
            {
                $this->fps = intval(str_replace('-1', '', $video_info['streams'][0]['avg_frame_rate']));
            }
            else
            {
                $msgs[] = 'no nb_read_frames or duration:' . $this->durationSeconds;
            }
        }
        $this->rateK = intval($video_rate / 1024);
        echo "\nwidth:{$this->width} height:{$this->height} fps:{$this->fps} bit_rate:{$this->rateK} duration:{$this->durationSeconds}\n****************************************************************\n";
        if (empty($this->fps) || empty($this->width) || empty($this->height) || empty($video_rate) || empty($this->rateK))
        {
            $msgs[] = "error: width:{$this->width} height:{$this->height} fps:{$this->fps} bit_rate:{$this->rateK} duration:{$this->durationSeconds}";
            return false;
        }
        $this->isPhone = $this->height > $this->width;
        return true;
    }

    public function initFilename()
    {
        $filename = $this->baseName;
        $ext      = strtolower($this->ext);
        $filename = "-{$filename}";
        $filename = preg_replace('/VIF_\d+P_H/isU', '', $filename);

        if ($filename !== $this->baseName)
        {
            $newFullPath = "{$this->dir}/{$filename}.{$ext}";
            rename($this->fullPath, $newFullPath);
            $this->reloadFileinfo($newFullPath);
        }
        if (!is_dir("{$this->dir}/FFOutput"))
        {
            exec("mkdir -p -m 777 '{$this->dir}/FFOutput'");
        }
    }

    public function isExist()
    {
        return file_exists($this->fullPath);
    }

    public function getHalfTrimFlagFullPath($flag = '')
    {
        $val = preg_replace('/^(-flag-)(\d{10}-)(\d+-)(\d+-)(flag\s)(.*)$/isU', '$1$2$3$4$5$6', $this->baseName);
        return "{$this->dir}/{$val}{$flag}.{$this->ext}";
    }

    public function getFullTrimFlagFullPath($flag = '')
    {
        $val = preg_replace('/^(-flag-)(\d{10}-)(\d+-)(\d+-)((flagFlag|flag)-\s)(.*)$/isU', '$7', $this->baseName);
        return "{$this->dir}/{$val}{$flag}.{$this->ext}";
    }




}