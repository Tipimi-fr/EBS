<?php

declare(strict_types=1);

namespace App\Mailer\Email\Command;

use App\Controller\i18nTrait;
use App\Entity\Group;
use App\Entity\User;
use App\Mailer\AppMailer;
use App\Mailer\Email\EmailInterface;
use App\Mailer\Email\EmailTrait;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * Message sent when a user was removed for a group when the endDate of his membership
 * is passed.
 */
final class EndMembershipEmail implements EmailInterface
{
    use EmailTrait;
    use i18nTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire('%brand%')]
        private readonly string $brand,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function getEmail(array $context): TemplatedEmail
    {
        /** @var ?User $user */
        $user = $context['user'] ?? null;
        Assert::isInstanceOf($user, User::class);

        /** @var ?Group $group */
        $group = $context['group'] ?? null;
        Assert::isInstanceOf($group, Group::class);

        return (new TemplatedEmail())
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject($this->translator->trans($this->getI18nPrefix().'.subject', [
                '%brand%' => $this->brand,
                '%group%' => $group->getName(),
            ], AppMailer::TR_DOMAIN))
            ->htmlTemplate('email/command/end_membership.html.twig')
            ->context($context)
        ;
    }
}
