import AvatarAnimation from './AvatarAnimation'

export default function PetAnimation({ pet }) {
  const mood = { rat_buon: 'buon', rat_vui: 'vui' }[pet.Animation] || pet.Animation
  return <AvatarAnimation ma={pet.LoaiPet} camXuc={mood} className={`pet-animation ${pet.TrangThaiPet}`} />
}
