<?php
/**
 * Created by PhpStorm.
 * User: victo
 * Date: 11/04/2018
 * Time: 20:27
 */

namespace pwbox\Controller;

use Psr\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;

class UploadController
{
    protected $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }



    public function uploadAction(Request $request, Response $response, array $args)
    {
        //RECIBE UN POST CON LA INFO??!
         //devuelve el id del archivo!
        $menu['dashboard']=true;
        $directory =  __DIR__ . '/uploads';

        $uploadedFiles = $request->getUploadedFiles();

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['file'];
        $error = '';
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $error = $this->moveUploadedFile($directory, $uploadedFile, $args['folder_id']);

        }

        $data["id"] = $args["folder_id"];
        $id = $data["id"];
        $service = $this->container->get('folders_inside_service');
        $folders = $service($data);
        $service = $this->container->get('files_inside_service');
        $files = $service($data);
        return $this->container->get('view')
            ->render($response, 'dashboard.twig', ['folders'=>$folders, 'id'=>$id, 'files'=>$files, 'menu'=>$menu, 'error'=>$error]);

    }

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param string $directory directory to which the file is moved
     * @param UploadedFile $uploaded file uploaded file to move
     * @return string filename of moved file
     */
    public function moveUploadedFile($directory, UploadedFile $uploadedFile, $folder)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $error='';

        if($extension!='pdf' && $extension!='jpg' && $extension!='png' && $extension!='gif' && $extension!='md' && $extension!='txt'){
            $error = "El formato del archivo no es soportado";
        }
        else{
            if($uploadedFile->getSize()>2e6){
                $error = "El tamaño del archivo es superior a 2Mb";
            }
            else{
                $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

                $data['basename'] = $basename;
                $data['filename'] = $uploadedFile->getClientFilename();
                $data['extension'] = $extension;
                $data['folder'] = $folder;
                $data['size']=$uploadedFile->getSize();
                $service = $this->container->get('upload_service');
                $service($data);
            }
        }

        return $error;
    }

}

