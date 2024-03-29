<?php

namespace App\Controller;

use App\Form\CartType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormBuilderInterface;
use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use App\Services\Cart\CartService;


class CartController extends AbstractController
{
    /**
     * @Route("/panier", name="cart_index")
     */
    public function index(CartService $cartService): Response
    {
        return $this->render('produit/Cart.html.twig', [
            'items' => $cartService->getFullCart(),
            'total' => $cartService->getTotal(),
        ]);
    }

    /**
     * @Route("/panierJson", name="panierJson")
     */
    public function panierJson(CartService $cartService, NormalizerInterface $normalizer): Response
    {
        $jsonContent=$normalizer->normalize($cartService->getFullCart(),'json',['groups'=>'post:read']);
        return new Response(json_encode($jsonContent));
    }

    /**
     * @Route("/panier/add/{id}", name="cart_add")
     */
    public function add($id, CartService $cartService)
    {
        $cartService->add($id);
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/addPanierJson", name="addPanierJson")
     * @Method("GET")
     */
    public function addPanierJson(CartService $cartService, Request $request)
    {
        $id = $request->get("id");
        $cartService->add($id);
        return new JsonResponse("Ajouté.");
    }

    /**
     * @Route("/panier/reduce/{id}", name="cart_reduce")
     */
    public function reduce($id, CartService $cartService)
    {
        $cartService->reduce($id);
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/reducePanierJson/{id}", name="reducePanierJson")
     */
    public function reducePanierJson($id, CartService $cartService)
    {
        $cartService->reduce($id);
        return new JsonResponse("na99ast.");
    }

    /**
     * @Route("/panier/addQuantity/{id}", name="cart_addQuantity")
     */
    public function quantityCheck($id, CartService $cartService, Request $request)
    {
        $form = $this->createForm(CartType::class);
        $form->handleRequest($request);
        $qte=$form->getData('qte');
        $cartService->addQuantity($id, $qte);
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @Route("/panier/remove/{id}", name="cart_remove")
     */
    public function remove($id, CartService $cartService)
    {
        $cartService->remove($id);
        return $this->redirectToRoute('cart_index');

    }

    /**
     * @Route("/removeFromPanierJson/{id}", name="removeFromPanierJson")
     */
    public function removeFromPanierJson($id, CartService $cartService)
    {
        $cartService->remove($id);
        return new JsonResponse("deleted.");

    }

    /**
     * @Route("/error", name="error")
     */
    public function error()
    {
        return $this->render('home/404.html.twig');
    }

    /**
     * @Route("/checkout", name="checkout")
     */
    public function checkout(CartService $cartService, $stripeSK)
    {
        \Stripe\Stripe::setApiKey($stripeSK);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Total Commande',
                    ],
                    'unit_amount' => $cartService->getTotal()*100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('AjouterCommande', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('error', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        //return $this->redirectToRoute('AjouterCommande');
        return $this->redirect($session->url, 303);
    }




}