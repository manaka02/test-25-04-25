<?php

namespace App\Services\Import;


use App\Entity\Utilisateur;
use App\Entity\UtilisateurRole;
use App\Services\UtilityServices;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImportDefaultServices
{


    public function __construct(
        private ManagerRegistry       $managerRegistry,
        private ParameterBagInterface $params,
        private UtilityServices       $utilityServices,
    )
    {
    }


    public function importEntity($nameEntity, $namefile, $io, $purge = 1): bool
    {
        try {
            $body = "App\\Entity\\" . $nameEntity;
            $datas = json_decode(file_get_contents($this->params->get('data_directory') . "/" . $namefile . ".json"), true, 512, JSON_THROW_ON_ERROR);
            $entity = new $body();
            $this->utilityServices->setEntity($entity);
            $attributes = $this->utilityServices->getAttributes();
            $repository = $this->managerRegistry->getRepository($entity::class);
            if ($purge) {
                $oldData = $repository->findAll();
                foreach ($oldData as $oldDatum) {
                    $this->managerRegistry->getManager()->remove($oldDatum);
                }
                $this->managerRegistry->getManager()->flush();
            }
            foreach ($datas as $data) {
                $existedEntity = null;
                if (array_key_exists('code', $data)) {
                    $existedEntity = $repository->findOneBy(['code' => $data['code']]);
                }
                if (is_null($existedEntity)) {
                    $className = $this->managerRegistry->getManager()->getMetadataFactory()->getMetadataFor(get_class($entity))->getName();
                    $existedEntity = new $className();

                }
                foreach ($attributes as $attribute) {
                    if (array_key_exists($attribute->name, $data)) {
                        if (is_array($data[$attribute->name])) {

                            $subEntity = $this->managerRegistry->getRepository("App\\Entity\\" . $data[$attribute->name]['entity'])->findOneBy(['code' => $data[$attribute->name]['code']]);

                            if (!is_null($subEntity)) {
                                $setter = $this->utilityServices->getSetterMethod($attribute->name);
                                if (!is_null($setter)) {
                                    $existedEntity->$setter($subEntity);
                                }
                            }
                        } else {
                            $setter = $this->utilityServices->getSetterMethod($attribute->name);
                            if (!is_null($setter)) {
                                $existedEntity->$setter($data[$attribute->name]);
                            }
                        }
                    }
                }
                $this->managerRegistry->getManager()->persist($existedEntity);

            }
            $this->managerRegistry->getManager()->flush();

            $io->success('Added successfully ');
            return true;
        } catch (\Exception $e) {
            $io->warning($e->getMessage());
            return false;
        }
    }
    public function createUser(string $email, string $name, string $password, string $role)
    {

        $role = $this->managerRegistry->getRepository(UtilisateurRole::class)->findOneBy(['code' => $role]);

        if (!$role) {
            throw new \Exception("Role not found");
        }

        if (!$this->utilityServices->isValidEmail($email)) {
            throw new \Exception("Email not valid");
        }

        if (!$this->utilityServices->isValidPassword($password)) {
            throw new \Exception("le mot de passe doit contenir au moins 8 caractères dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial");
        }

        $user = new Utilisateur();
        $user->setEmail($email)
            ->setNomPrenom($name)
            ->setUtilisateurRole($role);
        $user->setPassword(
            $password
        );
        $user->hashPassword();

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();
    }

    public function importActivityCategories(): void
    {
        $data = json_decode(file_get_contents($this->params->get('data_directory') . "/activityCategory.json"), true, 512, JSON_THROW_ON_ERROR);

        $allSeasons = $this->managerRegistry->getRepository(ActivityCategorySeason::class)->findAll();
        $allSeasons = $this->utilityServices->indexBy($allSeasons);


        foreach ($data as $oneLine) {
            $category = new ActivityCategory();

            $category->setCode($oneLine['code'])
                ->setDesignation($oneLine['designation']);

            $listSeasons = explode(",", $oneLine['season']);
            foreach ($listSeasons as $season) {
                if (array_key_exists($season, $allSeasons)) {
                    $category->addSeason($allSeasons[$season]);
                }
            }

            $this->managerRegistry->getManager()->persist($category);

        }

        $this->managerRegistry->getManager()->flush();
    }
}