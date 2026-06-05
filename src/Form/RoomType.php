<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la salle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('floor', IntegerType::class, [
                'label' => 'Étage',
                'attr' => ['class' => 'form-control']
            ])
            ->add('equipment', TextareaType::class, [
                'label' => 'Équipements (séparés par des virgules)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}
