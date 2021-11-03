<?php

namespace App\Controller;
use App\Entity\User;
use Service\Paginate;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Form\ProductFormType;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\False_;
use phpDocumentor\Reflection\Types\True_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Faker\Factory;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProductController extends AbstractController
{
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/create', name: 'product')]
    public function createProduct()
    {
        $faker = Factory::create();
        $entityManager = $this->getDoctrine()->getManager();
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find(1);
        $user->setRoles(['ROLE_ADMIN']);
        $em->persist($user);
        $em->flush();
//        $product = new Product();
//        $product->setTitle($faker->name);
//        $product->setPrice($faker->randomNumber());
//        $product->setDescription($faker->streetName);
//        $entityManager->persist($product);
//        $entityManager->flush();

//        return $this->render('product/index.html.twig', [
//            'product_id' => $product->getId(),
//        ]);
    }
    #[Route('/homepage/{page}', defaults:['page'=>1])]
    public function show($page, Request $request):Response
    {
        $json = file_get_contents('https://www.boredapi.com/api/activity');
        $data = json_decode($json);

        $rp = $this->getDoctrine()
            ->getRepository(Product::class)
            ->getAllProducts()
        ;
        $paginate = new Paginate();
        $products = $paginate->make(4,$rp,$page);
        return $this->render('product/aboutProducts.html.twig',['products'=>$products[0],'maxPages'=>$products[1],'thisPage'=>$page, 'activity'=>$data]);
    }
    #[Route('/result/show/{page}')]
    public function listProduct(Request $request, $page=1):Response
    {
        $em = $this->getDoctrine()
            ->getManager();
        $data = $request->get('search');
        if (isset($data) or $page>=1) {
            $sql = $em->createQuery("SELECT u FROM App\Entity\Product u WHERE u.title LIKE '%$data%'");
            $paginator = new Paginate();
            $products = $paginator->make(4,$sql,$page);
            return $this->render('product/productList.html.twig',['result'=>$products[0],'maxPages'=>$products[1],'thisPage'=>$page]);
        }else{
            return $this->render('product/NotFind.html.twig');
        }
    }
    #[Route('/add/product')]
    public function new(EntityManagerInterface $entityManager, Request $request){
        if(!$this->security->isGranted("ROLE_ADMIN")){
            $this->addFlash('danger','You cannot enter');
            return $this->redirectToRoute('app_product_show');
        }
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findAll();

        $form = $this->createForm(ProductFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $this->addFlash('success','You add new product');
            $data = $form->getData();
            dump($data);die();
            $file = $form['img']->getData();
            // use the original file name
            $webPath = $this->getParameter('kernel.project_dir').'/public/img';
            $file->move($webPath, $file->getClientOriginalName());
            $fileName = $file->getClientOriginalName();
            $product = new Product();
            $product->setTitle($data->getTitle());
            $product->setPrice($data->getPrice());
            $product->setDescription($data->getDescription());
            $product->setImg($fileName);

            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('app_product_show');
        }
        return $this->render('add_product/new.html.twig',[
            'product_form'=>$form->createView(),'categories' =>$category
        ]);
    }
    #[Route('/show/{id}')]
    public function showOne(int $id):Response
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);
        if(!$product){
            return $this->render('product/NotFind.html.twig');
        }
        return $this->render('product/aboutProduct.html.twig',[
            'product_name'=>$product->getTitle(),
            'price'=>$product->getPrice(),
            'descr'=>$product->getDescription()
        ]);
    }
    #[Route('/update/product/{productId}')]
    public function updateProduct(int $productId,Request $request):Response
    {
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find($productId);
        if (!$product) {
            throw $this->createNotFoundException(
                'No product found for id '.$productId
            );
        }
        $form = $this->createForm(ProductFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $this->addFlash('success','You update product');
            $data = $form->getData();
            $file = $form['img']->getData();
            // use the original file name
            $webPath = $this->getParameter('kernel.project_dir').'/public/img';
            $file->move($webPath, $file->getClientOriginalName());
            $fileName = $file->getClientOriginalName();
            $product->setTitle($data->getTitle());
            $product->setPrice($data->getPrice());
            $product->setDescription($data->getDescription());
            $product->setImg($fileName);

            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('app_product_show');
        }
        return $this->render('add_product/new.html.twig',[
            'product_form'=>$form->createView(),
        ]);
    }
    #[Route('/delete/{id}')]
    public function deleteProduct($id):Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);
        $entityManager->remove($product);
        $entityManager->flush();
        $this->addFlash('success','Product is already deleted');
        return $this->redirectToRoute('app_product_show');
    }
    #[Route('/category/{categories}')]
    public function getCategory(string $categories):Response
    {
        $category = $this->getDoctrine()
                ->getRepository(Category::class)
                ->findBy(
                    ['name'=>$categories],[]
                );
        $products =$category[0]->getProducts();
        return $this->render('product/categoryShow.html.twig',['products'=>$products]);
    }
}
