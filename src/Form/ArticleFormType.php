<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;

class ArticleFormType extends AbstractType
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Article|null $article */
        $article = $options['data'] ?? null;
        $isEdit = $article && $article->getId();

        $builder
            ->add('title', TextType::class, [
                'help' => 'Choose something catchy!'
            ])
            ->add('content', null, [
                'rows' => 15
            ])
            ->add('author', UserSelectTextType::class, [
                'disabled' => $isEdit
            ])
            ->add('location', ChoiceType::class, [
                'placeholder' => 'Choose a location',
                'choices' => [
                    'The Solar System' => 'solar_system',
                    'Near a star' => 'star',
                    'Interstellar Space' => 'interstellar_space'
                ],
                'required' => false,
            ])
            /*
                The form that handles this page lives at src/Form/ArticleFormType.php.
                In ArticleAdminController if you scroll up a little bit here is the edit() action
                and you can see it using this ArticleFormType.
                Right now, this is a nice traditional form:
                it handles the request and saves the Article to the database.
                In ArticleFormType, add a new field with ->add()
                and call it imageFilename
                because that's the name of the property inside Article.
                For the type, use FileType::class.
                But there's a problem with this.
                And if you already see it, extra credit points for you!
                Move over and refresh.
                “The form's view data is expected to be an instance of class File but it is a string.”
                The problem is not super obvious
                but it clearly hates something about our new field.
                Here's the explanation:
                we know that when you upload a file,
                Symfony gives you an UploadedFile object, not a string.
                But, the imageFilename field here on Article that is a string!
             */
            /*
                How can we do that?
                Change the field name to just imageFile.
                There is no property on our entity with this name  so this, on its own, will not work.
                Pretty commonly, you'll see people create this property on their entity,
                just to make the form work.
                They don't persist this property to the database with Doctrine so the idea works,
                but I don't love it.
                Instead, we'll use a trick that we talked a lot about in our forms tutorial:
                add an option to the field:
                'mapped' => false.
             */
            /*
                Oh, but there is one tiny thing we need to clean up before moving on.
                What if we just want to, I don't know,
                edit the article's title,
                but we don't need to change the image.
                No problem - hit Update!
                Oh... That's HTML5 validation.
                You might remember from the forms tutorial
                that this required attribute is added to every field
                unless you're using form field type guessing.
                It's annoying - fix it by adding 'required' => false.
             */
            /*
                Normally we add validation to the entity class:
                we would go into the Article class,
                find the property, and add some annotations.
                But the field we want to validate is an unmapped form field:
                there is no imageFile property in Article.
                No worries: for unmapped fields,
                you can add validation directly to the form with the constraints option.
                And when it comes to file uploads,
                there are two really important constraints:
                one called File and an even stronger one called Image.
                Add new Image() - the one from the Validator\Constraints.
            */
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image()
                ]
            ]);
            /*
                Let's try it again.
                Refresh, change the title, submit and oof.
                “Call to a member function getClientOriginalName on null”
                Of course!
                We're not uploading a file!
                So the $uploadedFile variable is null! That's ok!
                If the user didn't upload a file,
                we don't need to do any of this logic.
                In other words, if ($uploadedFile), then do all of that. Otherwise, skip it!
             */
            /*
                Connecting the form field directly to the string property doesn't make sense.
                We're missing a layer in the middle:
                something that can work with the UploadedFile object, move the
                file, and then set the new filename onto the property.
             */

        if ($options['include_published_at']) {
            $builder->add('publishedAt', null, [
                'widget' => 'single_text',
            ]);
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var Article|null $data */
                $data = $event->getData();
                if (!$data) {
                    return;
                }

                $this->setupSpecificLocationNameField(
                    $event->getForm(),
                    $data->getLocation()
                );
            }
        );

        $builder->get('location')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) {
                $form = $event->getForm();
                $this->setupSpecificLocationNameField(
                    $form->getParent(),
                    $form->getData()
                );
            }
        );
    }

    private function setupSpecificLocationNameField(FormInterface $form, ?string $location)
    {
        if (null === $location) {
            $form->remove('specificLocationName');

            return;
        }

        $choices = $this->getLocationNameChoices($location);

        if (null === $choices) {
            $form->remove('specificLocationName');

            return;
        }

        $form->add('specificLocationName', ChoiceType::class, [
            'placeholder' => 'Where exactly?',
            'choices' => $choices,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'include_published_at' => false,
        ]);
    }

    private function getLocationNameChoices(string $location)
    {
        $planets = [
            'Mercury',
            'Venus',
            'Earth',
            'Mars',
            'Jupiter',
            'Saturn',
            'Uranus',
            'Neptune',
        ];

        $stars = [
            'Polaris',
            'Sirius',
            'Alpha Centauari A',
            'Alpha Centauari B',
            'Betelgeuse',
            'Rigel',
            'Other'
        ];

        $locationNameChoices = [
            'solar_system' => array_combine($planets, $planets),
            'star' => array_combine($stars, $stars),
            'interstellar_space' => null,
        ];

        return $locationNameChoices[$location] ?? null;
    }
}
