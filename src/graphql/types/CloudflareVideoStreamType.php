<?php

namespace deuxhuithuit\cfstream\graphql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CloudflareVideoStreamType extends ObjectType
{
    public $name = 'CloudflareVideoStream';

    public function __construct()
    {
        parent::__construct([
            'name' => $this->name,
            'fields' => [
                'uid' => [
                    'name' => 'uid',
                    'type' => Type::string(),
                    'description' => 'A Cloudflare-generated unique identifier for a media item.',
                    'resolve' => function ($value) {
                        if (!isset($value['uid'])) {
                            return null;
                        }
                        return strval($value['uid']);
                    },
                ],
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                    'description' => 'The name of the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['meta']['name'])) {
                            return null;
                        }
                        return strval($value['meta']['name']);
                    },
                ],
                'hls' => [
                    'name' => 'hls',
                    'type' => Type::string(),
                    'description' => 'The HLS manifest for the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['playback']['hls'])) {
                            return null;
                        }
                        return strval($value['playback']['hls']);
                    },
                ],
                'dash' => [
                    'name' => 'dash',
                    'type' => Type::string(),
                    'description' => 'DASH Media Presentation Description for the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['playback']['dash'])) {
                            return null;
                        }
                        return strval($value['playback']['dash']);
                    },
                ],
                'mp4' => [
                    'name' => 'mp4',
                    'type' => Type::string(),
                    'description' => 'The MP4 URL of the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['mp4Url'])) {
                            return null;
                        }
                        return strval($value['mp4Url']);
                    },
                ],
                'input' => [
                    'name' => 'input',
                    'resolve' => function ($value) {
                        if (!isset($value['input'])) {
                            return [];
                        }
                        return $value['input'];
                    },
                    'type' => new ObjectType([
                        'name' => 'input',
                        'fields' => [
                            'height' => [
                                'name' => 'height',
                                'type' => Type::int(),
                                'description' => 'The video height in pixels. A value of -1 means 
                                the height is unknown.',
                                'resolve' => function ($value) {
                                    if (!isset($value['height'])) {
                                        return null;
                                    }
                                    return intval($value['height']);
                                },
                            ],
                            'width' => [
                                'name' => 'width',
                                'type' => Type::int(),
                                'description' => 'The video width in pixels. A value of -1 means 
                                the height is unknown.',
                                'resolve' => function ($value) {
                                    if (!isset($value['width'])) {
                                        return null;
                                    }
                                    return intval($value['width']);
                                },
                            ],
                        ],
                    ]),
                ],
                'preview' => [
                    'name' => 'preview',
                    'type' => Type::string(),
                    'description' => 'The name of the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['preview'])) {
                            return null;
                        }
                        return strval($value['preview']);
                    },
                ],
                'size' => [
                    'name' => 'size',
                    'type' => Type::int(),
                    'description' => 'The size of the media item in bytes.',
                    'resolve' => function ($value) {
                        if (!isset($value['size'])) {
                            return null;
                        }
                        return intval($value['size']);
                    },
                ],
                'thumbnail' => [
                    'name' => 'thumbnail',
                    'type' => Type::string(),
                    'description' => 'The media item\'s thumbnail URI.',
                    'resolve' => function ($value) {
                        if (!isset($value['thumbnail'])) {
                            return null;
                        }
                        return strval($value['thumbnail']);
                    },
                ],
                'thumbnailTimestampPct' => [
                    'name' => 'thumbnailTimestampPct',
                    'type' => Type::string(),
                    'description' => 'The timestamp for a thumbnail image calculated as a percentage 
                    value of the video\'s duration. To convert from a second-wise timestamp to a 
                    percentage, divide the desired timestamp by the total duration of the video. 
                    If this value is not set, the default thumbnail image is taken from 0s of the video.',
                    'resolve' => function ($value) {
                        if (!isset($value['thumbnailTimestampPct'])) {
                            return null;
                        }
                        return strval($value['thumbnailTimestampPct']);
                    },
                ],
                'watermark' => [
                    'name' => 'watermark',
                    'resolve' => function ($value) {
                        if (!isset($value['watermark'])) {
                            return [];
                        }
                        return $value['watermark'];
                    },
                    'type' => new ObjectType([
                        'name' => 'watermark',
                        'fields' => [
                            'created' => [
                                'name' => 'created',
                                'description' => 'The date and a time a watermark profile was created.',
                                'type' => Type::string(),
                                'resolve' => function ($value) {
                                    if (!isset($value['created'])) {
                                        return null;
                                    }
                                    return strval($value['created']);
                                },
                            ],
                            'downloadedFrom' => [
                                'name' => 'downloadedFrom',
                                'description' => 'The source URL for a downloaded image. If the watermark 
                                profile was created via direct upload, this field is null.',
                                'type' => Type::string(),
                                'resolve' => function ($value) {
                                    if (!isset($value['downloadedFrom'])) {
                                        return null;
                                    }
                                    return strval($value['downloadedFrom']);
                                },
                            ],
                            'height' => [
                                'name' => 'height',
                                'description' => 'The height of the image in pixels.',
                                'type' => Type::int(),
                                'resolve' => function ($value) {
                                    if (!isset($value['height'])) {
                                        return null;
                                    }
                                    return intval($value['height']);
                                },
                            ],
                            'name' => [
                                'name' => 'name',
                                'description' => 'A short description of the watermark profile.',
                                'type' => Type::string(),
                                'resolve' => function ($value) {
                                    if (!isset($value['name'])) {
                                        return null;
                                    }
                                    return strval($value['name']);
                                },
                            ],
                            'opacity' => [
                                'name' => 'opacity',
                                'description' => 'The translucency of the image. A value of 0.0 
                                makes the image completely transparent, and 1.0 makes the image 
                                completely opaque. Note that if the image is already semi-transparent, 
                                setting this to 1.0 will not make the image completely opaque.',
                                'type' => Type::int(),
                                'resolve' => function ($value) {
                                    if (!isset($value['opacity'])) {
                                        return null;
                                    }
                                    return intval($value['opacity']);
                                },
                            ],
                            'padding' => [
                                'name' => 'padding',
                                'description' => 'The whitespace between the adjacent edges 
                                (determined by position) of the video and the image. 0.0 indicates 
                                no padding, and 1.0 indicates a fully padded video width or length, 
                                as determined by the algorithm.',
                                'type' => Type::int(),
                                'resolve' => function ($value) {
                                    if (!isset($value['padding'])) {
                                        return null;
                                    }
                                    return intval($value['padding']);
                                },
                            ],
                            'position' => [
                                'name' => 'position',
                                'description' => 'The location of the image. Valid positions are: 
                                upperRight, upperLeft, lowerLeft, lowerRight, and center. 
                                Note that center ignores the padding parameter.',
                                'type' => Type::string(),
                                'resolve' => function ($value) {
                                    if (!isset($value['position'])) {
                                        return null;
                                    }
                                    return strval($value['position']);
                                },
                            ],
                            'scale' => [
                                'name' => 'scale',
                                'description' => 'The size of the image relative to the overall size of the video. 
                                This parameter will adapt to horizontal and vertical videos automatically. 
                                0.0 indicates no scaling (use the size of the image as-is),
                                and 1.0 fills the entire video.',
                                'type' => Type::float(),
                                'resolve' => function ($value) {
                                    if (!isset($value['scale'])) {
                                        return null;
                                    }
                                    return floatval($value['scale']);
                                },
                            ],
                            'size' => [
                                'name' => 'size',
                                'description' => 'The size of the image in bytes.',
                                'type' => Type::int(),
                                'resolve' => function ($value) {
                                    if (!isset($value['size'])) {
                                        return null;
                                    }
                                    return intval($value['size']);
                                },
                            ],
                            'width' => [
                                'name' => 'width',
                                'description' => 'The width of the image in pixels.',
                                'type' => Type::int(),
                                'resolve' => function ($value) {
                                    if (!isset($value['width'])) {
                                        return null;
                                    }
                                    return intval($value['width']);
                                },
                            ],
                            'uid' => [
                                'name' => 'uid',
                                'description' => 'The unique identifier for a watermark profile.',
                                'type' => Type::string(),
                                'resolve' => function ($value) {
                                    if (!isset($value['uid'])) {
                                        return null;
                                    }
                                    return strval($value['uid']);
                                },
                            ],
                        ],
                    ]),
                ],
            ],
        ]);
    }
}
