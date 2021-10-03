<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields="username", groups={"create"}, message= "Ce nom d'utilisateur est déja utilisé")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("user:index")
     * 
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups("user:index")
     * @Assert\NotBlank(message="Le nom d'utilisateur doit être renseigné", groups={"create"})
     * @Assert\Length(min=3, max=180, minMessage="Le nom d'utilisateur doit avoir au moins 3 caractères", groups={"create"})
     * @Assert\Regex(
     *      "#^[a-zA-Z0-9._-]+$#", 
     *      message="Le nom d'utilisateur peut comporter des caractères alphanumériques, points, tirets et underscores",
     *      groups={"create"}
     * )
     * 
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     * 
     * @var array
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Le mot de passe doit être renseigné", groups={"create", "update"})
     * @Assert\Length(
     *      min=8, max=255, minMessage="Le mot de passe doit avoir au moins 8 charactères",
     *      groups={"create", "update"}
     * )
     */
    private $password;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="ownedUsers")
     * 
     * @var User
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="owner")
     * 
     * @var Collection
     */
    private $ownedUsers;

    public function __construct()
    {
        $this->ownedUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        #$roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getOwner(): ?self
    {
        return $this->owner;
    }

    public function setOwner(?self $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getOwnedUsers(): Collection
    {
        return $this->ownedUsers;
    }

    public function addOwnedUser(self $ownedUser): self
    {
        if (!$this->ownedUsers->contains($ownedUser)) {
            $this->ownedUsers[] = $ownedUser;
            $ownedUser->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedUser(self $ownedUser): self
    {
        if ($this->ownedUsers->removeElement($ownedUser)) {
            // set the owning side to null (unless already changed)
            if ($ownedUser->getOwner() === $this) {
                $ownedUser->setOwner(null);
            }
        }

        return $this;
    }
}
