<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 16.01.17
 * Time: 01:02
 */

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Place;
use App\Form\PlaceType;
use Symfony\Component\HttpFoundation\JsonResponse;

Class PlacesController extends AbstractController
{
    /**
     * @Route("/profile/places", name="places", methods={"GET"})
     * @return Response
     */
    public function editPlacesAction()
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('fos_user_security_login');
        }

        $place = new Place();
        $places = $this->getUser()->getPlaces();
        $form = $this->createForm(PlaceType::class, $place);

        return $this->render('places/places.html.twig', array(
            'places' => $places,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/profile/ajax_geo_save", name="ajax_geo_save", methods={"POST"})
     * @Route("/profile/ajax_geo_save/{debug}", methods={"POST"})
     */
    public function ajaxGeoSave(Request $request, $debug=null)
    {
        $content = $request->getContent();
        $params = json_decode($content, true);

        $formattedAddress = $params['formatted_address'];

        try {
            $address = $this->getAddress($formattedAddress);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }

        if($debug=="debug") { return new JsonResponse($address); }

        $place = $this->getPlace($address);

        // these lines persist user relation with place, not only place
        $em = $this->getDoctrine()->getManager();
        $em->persist($place);
        $em->flush();

        return new JsonResponse($address, 201);
    }

    /**
     * @Route("/profile/ajax_geo_location", name="ajax_geo_location", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function ajaxGeoLocation(Request $request)
    {
        $lon = $request->get('lon');
        $lat = $request->get('lat');

        try {
            $address = $this->getAddress([$lat, $lon]);// get address from coordinates
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }

        return new JsonResponse($address);
    }

    /**
     * @Route("/profile/ajax_geo_delete/{googleId}", name="ajax_geo_delete", methods={"DELETE"})
     * @param string googleId
     * @return JsonResponse
     * @throws \Exception
     */
    public function ajaxGeoDelete($googleId)
    {
        $place = $this->getDoctrine()->getRepository(Place::class)->findOneBy(array(
            'googleId' => $googleId
        ));

        if(!$place) { return new JsonResponse(["error"=>"Place Not Found"],404); }

        try {
            $address = $this->getAddress($place->getFormattedAddress());

            $place->removeUser($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->persist($place);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }

        return new JsonResponse($address,204);
    }

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function getAddress($data)
    {
        $GOOGLE_MAPS_API_KEY = getenv('GOOGLE_MAPS_API_KEY');

        if(is_string($data)){
            $address = str_replace(" ", "+", $data); // replace all the white space with "+" sign to match with google search pattern
            $url = "https://maps.google.com/maps/api/geocode/json?key=$GOOGLE_MAPS_API_KEY&sensor=false&address=$address";
        } elseif (is_array($data) && count($data)) {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?key=$GOOGLE_MAPS_API_KEY&latlng=$data[0],$data[1]&sensor=false";
        } else {
            throw new \Exception("Incorrect args, put string or array with lat and lon");
        }

        $response = file_get_contents($url);
        $json = json_decode($response, TRUE); //generate array object from the response from the web

        if(array_key_exists("error_message", $json)) {
            throw new \Exception($json['error_message']);
        }
//        dump($json);die;

        return $json['results'][0];
    }

    /**
     * @param array $address
     * @return mixed
     */
    public function getPlace($address)
    {
        $place = $this->getDoctrine()->getRepository(Place::class)->findOneBy(array(
            'googleId' => $address['place_id']
        ));

        if($place === null)
        {
            $place = new Place();

            $place->setGoogleId($address['place_id']);
            $place->setLat($address['geometry']['location']['lat']);
            $place->setLon($address['geometry']['location']['lng']);
            $place->setFormattedAddress($address['formatted_address']);

            $params = $place->getParams();

            foreach($address["address_components"] as $component){
                foreach($params as $paramId => $param){
                    if(in_array($param,$component["types"])){
                        $place->setParam($param,$component["long_name"]);
                        unset($params[$paramId]);
                    }
                }
            }
        }

        $place->addUsers($this->getUser());

        return $place;

    }
}