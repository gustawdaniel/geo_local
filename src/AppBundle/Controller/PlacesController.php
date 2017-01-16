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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Place;
use AppBundle\Form\PlaceType;
use Symfony\Component\HttpFoundation\JsonResponse;
//use AppBundle\Entity\User;

Class PlacesController extends Controller
{
    /**
     * @Route("/profile/places", name="places")
     * @param Request $request
     * @return Response
     */
    public function editPlacesAction(Request $request)
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('fos_user_security_login');
        }

        $place = new Place();
        $places = $this->getUser()->getPlaces();
        $form = $this->createForm(PlaceType::class, $place);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        }

        return $this->render(':places:places.html.twig', array(
            'data' => $this->getAddress(52.2844037, 20.969362699999998),
            'places' => $places,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/profile/ajax_geo_save", name="ajax_geo_save")
     */
    public function ajaxGeoSave(Request $request)
    {
        $params = array();
        $content = $request->getContent();

        if (!empty($content))
        {
            $params = json_decode($content, true); // 2nd param to get as array
        }

        $formatted_address = $params['formatted_address'];

//        return new JsonResponse($params);

        $address = $this->addressToArray($formatted_address);


        $place = $this->getPlace($address);

        // these lines persist user relation with place, not only place, don't delete
        $em = $this->getDoctrine()->getManager();
        $em->persist($place);
        $em->flush();
        $code = 200;

//        $place = $this->getPlace($address);
//
//        // these lines persist user relation with place, not only place, don't delete
//        $em = $this->getDoctrine()->getManager();
//        $em->persist($place);
//        $em->flush();

        return new JsonResponse($address, $code);
    }

    /**
     * @Route("/profile/ajax_geo_location", name="ajax_geo_location")
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGeoLocation(Request $request)
    {
        $lon = $request->get('lon');
        $lat = $request->get('lat');

        $address = $this->getAddress($lat,$lon);

        $code =200;

        return new JsonResponse($address,$code);
    }

    /**
     * @Route("/profile/ajax_geo_delete", name="ajax_geo_delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxGeoDelete(Request $request)
    {
        $googleId = $request->get('google_id');

        $place = $this->getDoctrine()->getRepository("AppBundle:Place")->findOneBy(array(
            'googleId' => $googleId
        ));

        $address = $this->addressToArray($place->getFormattedAddress());


    //        $this->getUser()->removePlace($place);
        $place->removeUser($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($place);
        $em->flush();

        $code =200;

        return new JsonResponse($address,$code);
    }

    //public function showUserExternalDataAction(User $user)
    //{
    //    return $this->render('AppBundle:Profile:show_external_data.html.twig', array(
    //        'data' => $this->getAddress(52.2844037, 20.969362699999998),
    //    ));
    //}

    public function getCoordinates($formatted_address)
    {
        $formatted_address = str_replace(" ", "+", $formatted_address); // replace all the white space with "+" sign to match with google search pattern

        $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$formatted_address";

        $response = file_get_contents($url);

        $json = json_decode($response, TRUE); //generate array object from the response from the web

        return array(
            $json['results'][0]['geometry']['location']['lat'],
            $json['results'][0]['geometry']['location']['lng']
        );
    }

    public function getAddress($lat, $long)
    {
        $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&sensor=false";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        $curlData = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($curlData,true);
        return $response["results"][0];
    }

    /**
     * @param array $components
     * @return array $response
     */
    public function addressComponentTransform($components)
    {
        $response = [];
        foreach($components as $component)
        {
            $response[$component["types"][0]]= $component["long_name"];
        }
        return $response;
    }

    /**
     * @param $address
     * @return array
     */
    public function addressToArray($address)
    {
        $address = str_replace(" ", "+", $address); // replace all the white space with "+" sign to match with google search pattern
        $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";
        $response = file_get_contents($url);
        $json = json_decode($response, TRUE); //generate array object from the response from the web
        return $json['results'][0];
    }

    /**
     * @param $lon
     * @param $lat
     * @return array
     */
    public function coordinatesToArray($lon, $lat)
    {
        $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lon&sensor=false";
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

            $format_components = $this->addressComponentTransform($address["address_components"]);

            if(in_array('country',$format_components)) {
                $place->setCountry($format_components["country"]);
            }
            if(in_array('administrative_area_level_1',$format_components)) {
                $place->setAdministrativeAreaLevel1($format_components["administrative_area_level_1"]);
            }
            if(in_array('administrative_area_level_2',$format_components)) {
                $place->setAdministrativeAreaLevel2($format_components["administrative_area_level_2"]);
            }
            if(in_array('locality',$format_components)) {
                $place->setLocality($format_components["locality"]);
            }
            if(in_array('sublocality_level_1',$format_components)){
                $place->setSublocalityLevel1($format_components["sublocality_level_1"]);
            }
            if(in_array('route',$format_components)) {
                $place->setRoute($format_components["route"]);
            }
            if(in_array('street_number',$format_components)){
                $place->setStreetNumber($format_components["street_number"]);
            }
        }

        $place->addUsers($this->getUser());

        return $place;

    }
}