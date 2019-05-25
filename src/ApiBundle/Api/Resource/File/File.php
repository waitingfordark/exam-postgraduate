<?php

namespace ApiBundle\Api\Resource\File;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use Biz\Content\FileException;
use Symfony\Component\HttpFoundation\File\File as FileObject;

class File extends AbstractResource
{
    public function add(ApiRequest $request)
    {
        $file = $request->request->get('file', null);
        $file = $this->fileDecode($file);
        if (empty($file)) {
            $file = $request->getHttpRequest()->files->get('file', null);
        }

        if (empty($file)) {
            throw FileException::FILE_NOT_UPLOAD();
        }

        $group = $request->request->get('group', null);
        if (!in_array($group, array('tmp', 'user', 'course'))) {
            throw FileException::FILE_GROUP_INVALID();
        }

        return $this->getFileService()->uploadFile($group, $file);
    }

    protected function fileDecode($str)
    {
        if (empty($str)) {
            return $str;
        }
        // data:{mimeType};base64,{code}
        $user = $this->getCurrentUser();
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $str, $result)) {
            $filePath = $this->biz['topxia.upload.public_directory'].'/tmp/'.$user['id'].'_'.time().'.'.$result[2];
            file_put_contents($filePath, base64_decode(str_replace($result[1], '', $str)));

            return new FileObject($filePath);
        }

        return null;
    }

    protected function getFileService()
    {
        return $this->service('Content:FileService');
    }
}
