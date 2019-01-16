<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 16.01.17
 * Time: 01:02
 */

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Place;
use AppBundle\Form\PlaceType;
use Symfony\Component\HttpFoundation\JsonResponse;

Class PlacesController extends Controller
{
    /**
     * @Route("/profile/places", name="places")
     * @Method("GET")
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

        return $this->render(':places:places.html.twig', array(
            'places' => $places,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/profile/ajax_geo_save", name="ajax_geo_save")
     * @Route("/profile/ajax_geo_save/{debug}")
     * @Method("POST")
     */
    public function ajaxGeoSave(Request $request, $debug=null)
    {
        $content = $request->getContent();
        $params = json_decode($content, true);

        $formattedAddress = $params['formatted_address'];
        $address = $this->getAddress($formattedAddress);

        if($debug=="debug") { return new JsonResponse($address); }

        $place = $this->getPlace($address);

        // these lines persist user relation with place, not only place
        $em = $this->getDoctrine()->getManager();
        $em->persist($place);
        $em->flush();

        return new JsonResponse($address, 201);
    }

    /**
     * @Route("/profile/ajax_geo_location", name="ajax_geo_location")
     * @param Request $request
     * @Method("GET")
     * @return JsonResponse
     * @throws \Exception
     */
    public function ajaxGeoLocation(Request $request)
    {
        $lon = $request->get('lon');
        $lat = $request->get('lat');

        $address = $this->getAddress([$lat,$lon]);// get address from coordinates

        return new JsonResponse($address);
    }

    /**
     * @Route("/profile/ajax_geo_delete/{googleId}", name="ajax_geo_delete")
     * @Method("DELETE")
     * @param googleId
     * @return JsonResponse
     * @throws \Exception
     */
    public function ajaxGeoDelete($googleId)
    {
        $place = $this->getDoctrine()->getRepository("AppBundle:Place")->findOneBy(array(
            'googleId' => $googleId
        ));

        if(!$place) { return new JsonResponse(["error"=>"Place Not Found"],404); }

        $address = $this->getAddress($place->getFormattedAddress());

        $place->removeUser($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($place);
        $em->flush();

        return new JsonResponse($address,204);
    }

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function getAddress($data)
    {
        if(is_string($data)){
            $address = str_replace(" ", "+", $data); // replace all the white space with "+" sign to match with google search pattern
            $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";
        } elseif (is_array($data) && count($data)) {
            $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$data[0],$data[1]&sensor=false";
        } else {
            throw new \Exception("Incorrect args, put string or array with lat and lon");
        }

        $response = file_get_contents($url);
        $json = json_decode($response, TRUE); //generate array object from the response from the web
        return $json['results'][0];
    }

    /**
     * @param array $address
     * @return mixed
     */
    public function getPlace($address)
    {
        $place = $this->getDoctrine()->getRepository("AppBundle:Place")->findOneBy(array(
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