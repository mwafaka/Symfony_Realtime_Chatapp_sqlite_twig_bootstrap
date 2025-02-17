# Creating a real-time chat application using Symfony CLI version 5.10.6 and sqlite and boostsrap for styling.


###  1. Set Up Symfony Project
1. Install Symfony CLI (if not already installed):

```bash
curl -sS https://get.symfony.com/cli/installer | bash
```
2. Create a new Symfony project:

```bash
symfony new realtime-chat --version=5.x --webapp
cd realtime-chat
```

### 2. Install SQLite support:

```bash
composer require symfony/orm-pack
composer require --dev symfony/maker-bundle
```
### 3.Configure SQLite in .env:

- Open the .env file and set the DATABASE_URL to use SQLite:

```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### 4. Create the database:

```bash
php bin/console doctrine:database:create
```

### 5. Create Entities
1. Generate a Message entity:

```bash
php bin/console make:entity Message
```

2. Add the following fields:

- content (string, length: 255)
- createdAt (datetime)
- username (string, length: 50)

### 6. Update the Message entity (src/Entity/Message.php):

```bash
<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $content;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 50)]
    private $username;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
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
}
```

### 7. Create and run the migration:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 8. Create a Controller:

```bash
php bin/console make:controller ChatController
```

### 9. Update the ChatController (src/Controller/ChatController.php):

```bash
<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('chat');
        }

        $messages = $em->getRepository(Message::class)->findAll();

        return $this->render('chat/index.html.twig', [
            'form' => $form->createView(),
            'messages' => $messages,
        ]);
    }
      }

    #[Route('/chat/messages', name: 'chat_messages')]
public function messages(EntityManagerInterface $em): Response
{
    $messages = $em->getRepository(Message::class)->findAll();

    return $this->render('chat/messages.html.twig', [
        'messages' => $messages,
    ]);
}
}

```


### 10. Create a Form

```bash
php bin/console make:form MessageType
```

### 11. Update the form (src/Form/MessageType.php):

```bash
<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('content', TextType::class)
            ->add('send', SubmitType::class, ['label' => 'Send']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
```

### 12.  Create a Template
- Update the template (templates/chat/index.html.twig):

```bash
{% extends 'base.html.twig' %}

{% block title %}Chat{% endblock %}

{% block body %}
    <div class="container mt-5">
        <h1 class="text-center mb-4">Real-Time Chat</h1>

        <div id="chat" class="border p-3 rounded shadow-sm mb-4" style="height: 400px; overflow-y: scroll;">
            {# Messages will be loaded here dynamically #}
            {% include 'chat/messages.html.twig' with {'messages': messages} %}
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Send a Message</h5>
                {{ form_start(form, {'attr': {'class': 'd-flex flex-column'}}) }}
                    {{ form_widget(form) }}
                    <button type="submit" class="btn btn-primary mt-2">Send</button>
                {{ form_end(form) }}
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script>
            const chatDiv = document.getElementById('chat');
            const form = document.querySelector('form');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);

                try {
                    await axios.post('{{ path('chat') }}', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    });
                    form.reset(); // Clear the form fields
                    loadMessages(); // Reload messages
                } catch (error) {
                    console.error('Error submitting form:', error);
                }
            });

            async function loadMessages() {
                try {
                    const response = await axios.get('{{ path('chat_messages') }}');
                    chatDiv.innerHTML = response.data;
                } catch (error) {
                    console.error('Error loading messages:', error);
                }
            }

            // Load messages every second
            setInterval(loadMessages, 5000);
        </script>
    </div>
{% endblock %}

```

### 13. Run the Application
 - Start the Symfony server:

 ```bash
 symfony serve --no-tls
 ```


 ## Troubleshooting
- Issue: Terminal Flooded with Logs

- If the terminal is flooded with logs, reduce the logging verbosity by updating config/packages/dev/monolog.yaml:


```yaml 

monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: warning # Change this to "warning" or "error"
```